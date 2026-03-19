<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findBySearch(string $query, $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.companyName LIKE :q
              OR c.firstName LIKE :q
              OR c.lastName LIKE :q
              OR c.email LIKE :q
              OR c.city LIKE :q
              OR c.businessSector LIKE :q')
            ->andWhere('c.user = :user')
            ->setParameter('q', '%' . $query . '%')
            ->setParameter('user', $user)
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
