<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(ClientRepository $clientRepository, ProjectRepository $projectRepository): Response
    {
        $user = $this->getUser();

        // ROLE_CLIENT : dashboard simplifié avec ses projets
        if (!$this->isGranted('ROLE_ADMIN') && $user->getClientProfile()) {
            $clientProfile = $user->getClientProfile();
            $projects = $projectRepository->findBy(['client' => $clientProfile]);

            return $this->render('dashboard/client.html.twig', [
                'client'   => $clientProfile,
                'projects' => $projects,
            ]);
        }

        // ROLE_ADMIN : dashboard complet
        $clients = $clientRepository->findBy(
            ['user' => $user],
            ['id' => 'DESC'],
            5
        );

        return $this->render('dashboard/index.html.twig', [
            'clients' => $clients,
        ]);
    }
}
