<?php

namespace App\Controller;

use App\Entity\Messagrie;
use App\Repository\ClientRepository;
use App\Repository\MessagrieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messagrie')]
#[IsGranted('ROLE_USER')]
class MessagrieController extends AbstractController
{
    /**
     * Vue freelance : accéder à la messagerie d'un client spécifique.
     */
    #[Route('/client/{id}', name: 'app_messagrie_client', methods: ['GET', 'POST'])]
    public function client(
        int                    $id,
        Request                $request,
        ClientRepository       $clientRepository,
        MessagrieRepository    $messagrieRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $client = $clientRepository->find($id);

        if (!$client || $client->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $content = trim($request->request->get('content', ''));
            if ($content !== '') {
                $message = new Messagrie();
                $message->setContent($content);
                $message->setClient($client);
                $message->setUser($this->getUser());
                $message->setIsFromClient(false);
                $message->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($message);
                $entityManager->flush();
            }
            return $this->redirectToRoute('app_messagrie_client', ['id' => $id]);
        }

        $messages = $messagrieRepository->findBy(
            ['client' => $client],
            ['createdAt' => 'ASC']
        );

        return $this->render('messagrie/chat.html.twig', [
            'client'      => $client,
            'messages'    => $messages,
            'isFreelance' => true,
        ]);
    }

    /**
     * Vue client : accéder à sa propre messagerie avec le freelance.
     */
    #[Route('/my', name: 'app_messagrie_my', methods: ['GET', 'POST'])]
    public function my(
        Request                $request,
        MessagrieRepository    $messagrieRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $clientProfile = $user->getClientProfile();

        if (!$clientProfile) {
            throw $this->createAccessDeniedException('Vous n\'avez pas de profil client.');
        }

        if ($request->isMethod('POST')) {
            $content = trim($request->request->get('content', ''));
            if ($content !== '') {
                $message = new Messagrie();
                $message->setContent($content);
                $message->setClient($clientProfile);
                $message->setUser($clientProfile->getUser()); // le freelance propriétaire
                $message->setIsFromClient(true);
                $message->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($message);
                $entityManager->flush();
            }
            return $this->redirectToRoute('app_messagrie_my');
        }

        $messages = $messagrieRepository->findBy(
            ['client' => $clientProfile],
            ['createdAt' => 'ASC']
        );

        return $this->render('messagrie/chat.html.twig', [
            'client'      => $clientProfile,
            'messages'    => $messages,
            'isFreelance' => false,
        ]);
    }
}
