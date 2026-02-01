<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
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
         * @var Collection<int, Card> $cards
         * @return Card[] Returns an array of Card objects
         */
        public function findAllNotIn($cards): array
        {
            $qb = $this->createQueryBuilder('c');

            if (!$cards->isEmpty()) {
                $ids = $cards->map(fn (Card $card) => $card->getId())->toArray();
                $qb->andWhere($qb->expr()->notIn('c.id', ':ids'))
                   ->setParameter('ids', $ids);
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
