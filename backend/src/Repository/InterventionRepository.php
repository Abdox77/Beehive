<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Hive;
use App\Entity\Intervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Intervention>
 */
class InterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Intervention::class);
    }

    public function findByHiveOrderByDate(Hive $hive): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.hive = :hive')
            ->setParameter('hive', $hive)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();            
    }
}
