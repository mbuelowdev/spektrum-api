<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

        /**
         * @return Card[] Returns an array of Card objects
         */
        public function findAllNotIn($arrCardIds): array
        {
            $qb = $this->createQueryBuilder('c');

            if (!empty($arrCardIds)) {
                $qb->andWhere($qb->expr()->notIn('c.id', ':ids'))
                   ->setParameter('ids', $arrCardIds);
            }

            return $qb
                ->orderBy('c.id', 'ASC')
                ->getQuery()
                ->getResult();
        }

    //    public function findOneBySomeField($value): ?Card
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
