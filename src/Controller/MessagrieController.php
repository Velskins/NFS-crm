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

#[Route('/messagrie')]
class MessagrieController extends AbstractController
{
    #[Route('/client/{id}', name: 'app_messagrie_client', methods: ['GET', 'POST'])]
    public function client(
        int                    $id,
        Request                $request,
        ClientRepository       $clientRepository,
        MessagrieRepository    $messagrieRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
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

        return $this->render('messagrie/client.html.twig', [
            'client' => $client,
            'messages' => $messages,
        ]);
    }
}
