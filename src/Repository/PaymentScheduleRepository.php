<?php

namespace App\Repository;

use App\Entity\PaymentSchedule;
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

    /**
     * Somme des échéances PAYÉES dans l'année en cours pour un user.
     */
    public function getAnnualRevenue(\App\Entity\User $user): float
    {
        $year = (int) date('Y');
        $start = new \DateTimeImmutable($year . '-01-01');
        $end = new \DateTimeImmutable(($year + 1) . '-01-01');

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
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Somme de TOUTES les échéances prévues dans l'année en cours pour un user
     * (payées ou non — c'est l'objectif annuel).
     */
    public function getAnnualTarget(\App\Entity\User $user): float
    {
        $year = (int) date('Y');
        $start = new \DateTimeImmutable($year . '-01-01');
        $end = new \DateTimeImmutable(($year + 1) . '-01-01');

        $result = $this->createQueryBuilder('ps')
            ->select('SUM(ps.amount)')
            ->join('ps.project', 'p')
            ->where('p.user = :user')
            ->andWhere('ps.dueDate >= :start')
            ->andWhere('ps.dueDate < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
