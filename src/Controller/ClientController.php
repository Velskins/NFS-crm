<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/client')]
final class ClientController extends AbstractController
{
    #[Route(name: 'app_client_index', methods: ['GET'])]
    public function index(Request $request, ClientRepository $clientRepository): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');

        $clients = $search
            ? $clientRepository->findBySearch($search, $this->getUser())
            : $clientRepository->findBy(['user' => $this->getUser()]);

        // Filtrer par statut si demandé
        if ($status) {
            $clients = array_filter($clients, function ($client) use ($status) {
                $hasUnpaid = false;
                foreach ($client->getInvoices() as $invoice) {
                    if ($invoice->getStatus() !== 'Payée') {
                        $hasUnpaid = true;
                        break;
                    }
                }
                return $status === 'attente' ? $hasUnpaid : !$hasUnpaid;
            });
        }

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
            'search'  => $search,
            'status'  => $status,
        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client->setUser($this->getUser());
            $client->setPwd('pending'); // sera défini par le client via l'email

            // Créer un compte User (ROLE_CLIENT) lié à ce client
            $userAccount = new User();
            $userAccount->setEmail($client->getEmail());
            $userAccount->setFirstname($client->getFirstName());
            $userAccount->setLastname($client->getLastName());
            $userAccount->setRoles(['ROLE_CLIENT']);
            $userAccount->setPassword(''); // pas de mot de passe encore

            // Générer un token d'invitation (valable 48h)
            $token = bin2hex(random_bytes(32));
            $userAccount->setInvitationToken($token);
            $userAccount->setInvitationTokenExpiresAt(new \DateTimeImmutable('+48 hours'));

            // Lier User ↔ Client
            $client->setUserAccount($userAccount);

            $entityManager->persist($userAccount);
            $entityManager->persist($client);
            $entityManager->flush();

            // Envoyer l'email d'invitation
            $setupUrl = $this->generateUrl('app_setup_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new TemplatedEmail())
                ->from(new Address('noreply@sphera.app', 'SPHERA'))
                ->to($client->getEmail())
                ->subject('Bienvenue sur SPHERA — Définissez votre mot de passe')
                ->htmlTemplate('emails/invitation.html.twig')
                ->context([
                    'client'   => $client,
                    'setupUrl' => $setupUrl,
                    'expiresAt' => $userAccount->getInvitationTokenExpiresAt(),
                ]);

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Client "' . $client->getFirstName() . ' ' . $client->getLastName() . '" créé. Un email d\'invitation a été envoyé à ' . $client->getEmail() . '.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Client créé mais l\'email n\'a pas pu être envoyé. Lien de configuration : ' . $setupUrl);
            }

            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Client "' . $client->getFirstName() . ' ' . $client->getLastName() . '" modifié avec succès.');

            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_delete', methods: ['POST'])]
    public function delete(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($client);
            $entityManager->flush();

            $this->addFlash('success', 'Client supprimé avec succès.');
        }

        return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
    }
}