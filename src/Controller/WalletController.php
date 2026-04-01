<?php

namespace App\Controller;

use App\Repository\PaymentScheduleRepository;
use App\Repository\ProjectRepository;
use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class WalletController extends AbstractController
{
    #[Route('/wallet', name: 'app_wallet')]
    public function index(
        PaymentScheduleRepository $psRepo,
        ProjectRepository $projectRepo,
        ClientRepository $clientRepo,
    ): Response {
        $user = $this->getUser();

        // ── 1. KPIs principaux ───────────────────────────────────────────────

        // Argent encaissé (échéances payées, toutes périodes)
        $totalCollected = $psRepo->getTotalCollected($user);

        // Argent en attente (échéances non payées, pas encore en retard)
        $totalPending = $psRepo->getTotalPending($user);

        // Argent en retard (dueDate dépassée et non payée)
        $totalOverdue = $psRepo->getTotalOverdue($user);

        // Chiffre d'affaires total contractualisé (tous projets confondus)
        $totalContracted = $totalCollected + $totalPending + $totalOverdue;

        // ── 2. Revenus par mois (12 derniers mois) pour le graphique en barres ─
        $monthlyRevenue = $psRepo->getMonthlyRevenue($user, 12);

        // ── 3. Répartition par statut de projet (pour camembert) ─────────────
        $projectsByStatus = $psRepo->getRevenueByProjectStatus($user);

        // ── 4. Top 5 clients par CA généré ───────────────────────────────────
        $topClients = $psRepo->getTopClients($user, 5);

        // ── 5. Prochaines échéances (30 prochains jours) ─────────────────────
        $upcomingPayments = $psRepo->getUpcomingPayments($user, 30);

        // ── 6. Derniers encaissements ────────────────────────────────────────
        $recentPayments = $psRepo->getRecentPaidPayments($user, 8);

        // ── 7. Taux de recouvrement global ───────────────────────────────────
        $recoveryRate = $totalContracted > 0
            ? round(($totalCollected / $totalContracted) * 100, 1)
            : 0;

        return $this->render('wallet/index.html.twig', [
            'totalCollected'   => $totalCollected,
            'totalPending'     => $totalPending,
            'totalOverdue'     => $totalOverdue,
            'totalContracted'  => $totalContracted,
            'recoveryRate'     => $recoveryRate,
            'monthlyRevenue'   => $monthlyRevenue,
            'projectsByStatus' => $projectsByStatus,
            'topClients'       => $topClients,
            'upcomingPayments' => $upcomingPayments,
            'recentPayments'   => $recentPayments,
        ]);
    }
}
