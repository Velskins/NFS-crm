<?php

namespace App\Repository;

use App\Entity\Quote;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quote>
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    /**
     * Génère le prochain numéro de devis au format DEV-YYYY-XXX
     */
    public function generateQuoteNumber(): string
    {
        $year = date('Y');
        $prefix = 'DEV-' . $year . '-';

        // Chercher le dernier numéro de devis de l'année
        $lastQuote = $this->createQueryBuilder('q')
            ->where('q.quoteNumber LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('q.quoteNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastQuote) {
            // Extraire le numéro et incrémenter
            $lastNumber = (int) substr($lastQuote->getQuoteNumber(), -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Trouve les devis d'un utilisateur avec filtres optionnels
     * 
     * @return Quote[]
     */
    public function findByUserWithFilters(User $user, ?string $search = null, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('q')
            ->leftJoin('q.client', 'c')
            ->where('q.user = :user')
            ->setParameter('user', $user)
            ->orderBy('q.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('q.quoteNumber LIKE :search OR q.subject LIKE :search OR c.companyName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('q.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les devis par statut pour un utilisateur
     */
    public function countByStatus(User $user): array
    {
        $result = $this->createQueryBuilder('q')
            ->select('q.status, COUNT(q.id) as count')
            ->where('q.user = :user')
            ->setParameter('user', $user)
            ->groupBy('q.status')
            ->getQuery()
            ->getResult();

        $counts = [
            Quote::STATUS_DRAFT => 0,
            Quote::STATUS_SENT => 0,
            Quote::STATUS_ACCEPTED => 0,
            Quote::STATUS_REFUSED => 0,
        ];

        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }
}
