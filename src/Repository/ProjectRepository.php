<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Recherche des projets par titre ou nom de société du client.
     *
     * @return Project[]
     */
    public function findBySearch(string $search, User $user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.client', 'c')
            ->andWhere('p.user = :user')
            ->andWhere('p.title LIKE :search OR c.companyName LIKE :search')
            ->setParameter('user', $user)
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
