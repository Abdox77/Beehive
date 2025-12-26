<?php

namespace App\Repository;

use App\Entity\Hive;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Twig\Node\Expression\ReturnBoolInterface;

/**
 * @extends ServiceEntityRepository<Hive>
 */
class HiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hive::class);
    }


    public function createHiveForUser(User $user, string $name, float $lat, float $lon): void
    {
        $hive = new Hive();
        $hive->setName($name);
        $hive->setLat($lat);
        $hive->setLng($lon);
        $hive->setOwner($user);

        $this->getEntityManager()->persist($hive);
        $this->getEntityManager()->flush();
    }

    public function findHiveById(int $id): ?Hive {
        try {
            $hive = $this->findOneBy(["id"=> $id]);
        }
        catch (\Exception $e) {
            return null;
        }
        return $hive;
    }


    public function deleteUserHive(User $user, int $hiveId): bool {
        try {
            $hive = $this->find($hiveId);
            if (!$hive || $hive->getOwner()->getId() !== $user->getId()) {
                return false;
            }
            $this->getEntityManager()->remove($hive);
            $this->getEntityManager()->flush();
        }
        catch (\Exception $e) {
            return false;
        }
        return true;   
    }


    public function findWithHarvests(int $id): ?Hive
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.harvest', 'harvest')
            ->addSelect('harvest')
            ->where('h.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function findWithOwner(int $id): ?Hive
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.owner', 'owner')
            ->addSelect('owner')
            ->where('h.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findWithOwnerAndHarvests(int $id): ?Hive
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.owner', 'owner')
            ->addSelect('owner')
            ->leftJoin('h.harvest', 'harvest')
            ->addSelect('harvest')
            ->where('h.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
