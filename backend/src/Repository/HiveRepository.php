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


//    /**
//     * @return Hive[] Returns an array of Hive objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('h.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Hive
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
