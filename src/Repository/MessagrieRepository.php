<?php

namespace App\Repository;

use App\Entity\Messagrie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Messagrie>
 */
class MessagrieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Messagrie::class);
    }

    public function findNewMessages(Client $client, int $lastId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.client = :client')
            ->andWhere('m.id > :lastId')
            ->setParameter('client', $client)
            ->setParameter('lastId', $lastId)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
