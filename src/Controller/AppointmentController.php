<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/appointment')]
class AppointmentController extends AbstractController
{
    #[Route('/new/{clientId}', name: 'app_appointment_new', methods: ['GET', 'POST'])]
    public function new(
        int                    $clientId,
        Request                $request,
        EntityManagerInterface $entityManager,
        ClientRepository       $clientRepository
    ): Response
    {
        $client = $clientRepository->find($clientId);

        if (!$client || $client->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $appointment = new Appointment();
        $appointment->setClient($client);
        $appointment->setUser($this->getUser());

        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // note nullable
            if (!$appointment->getNote()) {
                $appointment->setNote('');
            }
            $entityManager->persist($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Rendez-vous créé !');
            return $this->redirectToRoute('app_client_show', ['id' => $clientId]);
        }

        return $this->render('appointment/new.html.twig', [
            'form' => $form,
            'client' => $client,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_appointment_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        $clientId = $appointment->getClient()->getId();

        if ($this->isCsrfTokenValid('delete_appointment' . $appointment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Rendez-vous supprimé !');
        }

        return $this->redirectToRoute('app_client_show', ['id' => $clientId]);
    }
}
