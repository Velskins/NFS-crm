<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(ClientRepository $clientRepository): Response
    {
        $clients = $clientRepository->findBy(
            ['user' => $this->getUser()],
            ['id' => 'DESC'],
            5
        );

        return $this->render('dashboard/index.html.twig', [
            'clients' => $clients,
        ]);
    }
}