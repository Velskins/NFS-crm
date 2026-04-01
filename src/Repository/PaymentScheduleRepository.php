<?php

namespace App\Repository;

use App\Entity\PaymentSchedule;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentSchedule>
 */
class PaymentScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentSchedule::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KPIs existants (conservés)
    // ─────────────────────────────────────────────────────────────────────────

    public function getAnnualRevenue(User $user): float
    {
        $year  = (int) date('Y');
        $start = new \DateTimeImmutable($year . '-01-01');
        $end   = new \DateTimeImmutable(($year + 1) . '-01-01');

        $result = $this->createQueryBuilder('ps')
            ->select('SUM(ps.amount)')
            ->join('ps.project', 'p')
            ->where('p.user = :user')
            ->andWhere('ps.status = :status')
            ->andWhere('ps.paidAt >= :start')
            ->andWhere('ps.paidAt < :end')
            ->setParameter('user', $user)
            ->setParameter('status', 'paye')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getAnnualTarget(User $user): float
    {
        $year  = (int) date('Y');
        $start = new \DateTimeImmutable($year . '-01-01');
        $end   = new \DateTimeImmutable(($year + 1) . '-01-01');

        $result = $this->createQueryBuilder('ps')
            ->select('SUM(ps.amount)')
            ->join('ps.project', 'p')
            ->where('p.user = :user')
            ->andWhere('ps.dueDate >= :start')
            ->andWhere('ps.dueDate < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KPIs Wallet — nouveaux
    // ─────────────────────────────────────────────────────────────────────────

    /** Somme totale de toutes les échéances payées (tout temps). */
    public function getTotalCollected(User $user): float
    {
        $result = $this->createQueryBuilder('ps')
            ->select('SUM(ps.amount)')
            ->join('ps.project', 'p')
            ->where('p.user = :user')
            ->andWhere('ps.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'paye')
            ->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /** Somme des échéances non payées dont l'échéance n'est pas dépassée. */
    public function getTotalPending(User $user): float
    {
        $now = new \DateTimeImmutable();

        $result = $this->createQueryBuilder('ps')
            ->select('SUM(ps.amount)')
            ->join('ps.project', 'p')
            ->where('p.user = :user')
            ->andWhere('ps.status != :status')
            ->andWhere('ps.dueDate >= :now')
            ->setParameter('user', $user)
            ->setParameter('status', 'paye')
            ->setParameter('now', $now)
            ->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /** Somme des échéances non payées dont l'échéance est dépassée. */
    public function getTotalOverdue(User $user): float
    {
        $now = new \DateTimeImmutable();

        $result = $this->createQueryBuilder('ps')
            ->select('SUM(ps.amount)')
            ->join('ps.project', 'p')
            ->where('p.user = :user')
            ->andWhere('ps.status != :status')
            ->andWhere('ps.dueDate < :now')
            ->setParameter('user', $user)
            ->setParameter('status', 'paye')
            ->setParameter('now', $now)
            ->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Revenus encaissés mois par mois sur les N derniers mois.
     * Retourne un tableau [ ['month' => 'Jan 2025', 'total' => 1200.00], … ]
     */
    public function getMonthlyRevenue(User $user, int $months = 12): array
    {
        $from = (new \DateTimeImmutable("-{$months} months"))->format('Y-m-d');

        $conn = $this->getEntityManager()->getConnection();
        $sql  = '
        SELECT
            DATE_FORMAT(ps.paid_at, \'%b %Y\') AS month,
            DATE_FORMAT(ps.paid_at, \'%Y-%m\') AS sort_key,
            SUM(ps.amount)                     AS total
        FROM payment_schedule ps
        INNER JOIN project p ON ps.project_id = p.id
        WHERE p.user_id  = :user
          AND ps.status  = :status
          AND ps.paid_at >= :from
        GROUP BY sort_key, month
        ORDER BY sort_key ASC
    ';

        $rows = $conn->executeQuery($sql, [
            'user'   => $user->getId(),
            'status' => 'paye',
            'from'   => $from,
        ])->fetchAllAssociative();

        // Construire le tableau complet avec 0 pour les mois sans données
        $map = [];
        foreach ($rows as $row) {
            $map[$row['sort_key']] = (float) $row['total'];
        }

        $output = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $dt  = new \DateTimeImmutable("first day of -{$i} months");
            $key = $dt->format('Y-m');
            $output[] = [
                'month' => $dt->format('M Y'),
                'total' => $map[$key] ?? 0.0,
            ];
        }

        return $output;
    }

    /**
     * Revenus encaissés groupés par statut de projet.
     * Retourne [ ['status' => 'en_cours', 'total' => 3200.0], … ]
     */
    public function getRevenueByProjectStatus(User $user): array
    {
        $results = $this->createQueryBuilder('ps')
            ->select('p.status AS status', 'SUM(ps.amount) AS total')
            ->join('ps.project', 'p')
            ->where('p.user = :user')
            ->andWhere('ps.status = :paid')
            ->setParameter('user', $user)
            ->setParameter('paid', 'paye')
            ->groupBy('p.status')
            ->getQuery()->getArrayResult();

        return array_map(fn($r) => [
            'status' => $r['status'],
            'total'  => (float) $r['total'],
        ], $results);
    }

    /**
     * Top N clients par CA total encaissé.
     * Retourne [ ['name' => 'Google', 'total' => 8500.0], … ]
     */
    public function getTopClients(User $user, int $limit = 5): array
    {
        $results = $this->createQueryBuilder('ps')
            ->select('c.companyName AS name', 'SUM(ps.amount) AS total')
            ->join('ps.project', 'p')
            ->join('p.client', 'c')
            ->where('p.user = :user')
            ->andWhere('ps.status = :paid')
            ->setParameter('user', $user)
            ->setParameter('paid', 'paye')
            ->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getArrayResult();

        return array_map(fn($r) => [
            'name'  => $r['name'],
            'total' => (float) $r['total'],
        ], $results);
    }

    /**
     * Prochaines échéances à venir dans les N prochains jours.
     */
    public function getUpcomingPayments(User $user, int $days = 30): array
    {
        $now  = new \DateTimeImmutable();
        $end  = new \DateTimeImmutable("+{$days} days");

        return $this->createQueryBuilder('ps')
            ->join('ps.project', 'p')
            ->where('p.user = :user')
            ->andWhere('ps.status != :paid')
            ->andWhere('ps.dueDate >= :now')
            ->andWhere('ps.dueDate <= :end')
            ->setParameter('user', $user)
            ->setParameter('paid', 'paye')
            ->setParameter('now', $now)
            ->setParameter('end', $end)
            ->orderBy('ps.dueDate', 'ASC')
            ->setMaxResults(10)
            ->getQuery()->getResult();
    }

    /**
     * Derniers encaissements reçus.
     */
    public function getRecentPaidPayments(User $user, int $limit = 8): array
    {
        return $this->createQueryBuilder('ps')
            ->join('ps.project', 'p')
            ->where('p.user = :user')
            ->andWhere('ps.status = :paid')
            ->setParameter('user', $user)
            ->setParameter('paid', 'paye')
            ->orderBy('ps.paidAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}
