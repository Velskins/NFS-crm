<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\QuoteLine;
use App\Form\QuoteType;
use App\Repository\QuoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/quote')]
#[IsGranted('ROLE_ADMIN')]
final class QuoteController extends AbstractController
{
    #[Route(name: 'app_quote_index', methods: ['GET'])]
    public function index(Request $request, QuoteRepository $quoteRepository): Response
    {
        $user = $this->getUser();
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');

        $quotes = $quoteRepository->findByUserWithFilters($user, $search ?: null, $status ?: null);
        $statusCounts = $quoteRepository->countByStatus($user);

        return $this->render('quote/index.html.twig', [
            'quotes' => $quotes,
            'search' => $search,
            'status' => $status,
            'statusCounts' => $statusCounts,
        ]);
    }

    #[Route('/new', name: 'app_quote_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, QuoteRepository $quoteRepository): Response
    {
        $quote = new Quote();
        
        // Ajouter une ligne par défaut
        $line = new QuoteLine();
        $line->setQuantity('1');
        $line->setUnitPrice('0');
        $quote->addLine($line);

        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quote->setUser($this->getUser());
            $quote->setQuoteNumber($quoteRepository->generateQuoteNumber());
            $quote->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($quote);
            $entityManager->flush();

            $this->addFlash('success', 'Devis "' . $quote->getQuoteNumber() . '" créé avec succès.');

            return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quote/new.html.twig', [
            'quote' => $quote,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quote_show', methods: ['GET'])]
    public function show(Quote $quote): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérifier que le devis appartient à l'utilisateur connecté
        if ($quote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devis.');
        }

        return $this->render('quote/show.html.twig', [
            'quote' => $quote,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quote_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le devis appartient à l'utilisateur connecté
        if ($quote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devis.');
        }

        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Devis "' . $quote->getQuoteNumber() . '" modifié avec succès.');

            return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quote/edit.html.twig', [
            'quote' => $quote,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quote_delete', methods: ['POST'])]
    public function delete(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le devis appartient à l'utilisateur connecté
        if ($quote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devis.');
        }

        if ($this->isCsrfTokenValid('delete' . $quote->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($quote);
            $entityManager->flush();

            $this->addFlash('success', 'Devis supprimé avec succès.');
        }

        return $this->redirectToRoute('app_quote_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/pdf', name: 'app_quote_pdf', methods: ['GET'])]
    public function pdf(Quote $quote): Response
    {
        // Vérifier que le devis appartient à l'utilisateur connecté
        if ($quote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devis.');
        }

        return $this->render('quote/pdf.html.twig', [
            'quote' => $quote,
        ]);
    }

    #[Route('/{id}/duplicate', name: 'app_quote_duplicate', methods: ['POST'])]
    public function duplicate(Request $request, Quote $quote, EntityManagerInterface $entityManager, QuoteRepository $quoteRepository): Response
    {
        // Vérifier que le devis appartient à l'utilisateur connecté
        if ($quote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devis.');
        }

        if ($this->isCsrfTokenValid('duplicate' . $quote->getId(), $request->getPayload()->getString('_token'))) {
            $newQuote = new Quote();
            $newQuote->setUser($this->getUser());
            $newQuote->setClient($quote->getClient());
            $newQuote->setSubject($quote->getSubject() . ' (copie)');
            $newQuote->setNotes($quote->getNotes());
            $newQuote->setStatus(Quote::STATUS_DRAFT);
            $newQuote->setQuoteNumber($quoteRepository->generateQuoteNumber());
            $newQuote->setCreatedAt(new \DateTimeImmutable());
            $newQuote->setValidUntil(new \DateTimeImmutable('+30 days'));

            // Dupliquer les lignes
            foreach ($quote->getLines() as $line) {
                $newLine = new QuoteLine();
                $newLine->setDescription($line->getDescription());
                $newLine->setQuantity($line->getQuantity());
                $newLine->setUnitPrice($line->getUnitPrice());
                $newLine->setPosition($line->getPosition());
                $newQuote->addLine($newLine);
            }

            $entityManager->persist($newQuote);
            $entityManager->flush();

            $this->addFlash('success', 'Devis dupliqué avec succès.');

            return $this->redirectToRoute('app_quote_edit', ['id' => $newQuote->getId()]);
        }

        return $this->redirectToRoute('app_quote_index');
    }

    #[Route('/{id}/status/{newStatus}', name: 'app_quote_change_status', methods: ['POST'])]
    public function changeStatus(Request $request, Quote $quote, string $newStatus, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le devis appartient à l'utilisateur connecté
        if ($quote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devis.');
        }

        $validStatuses = [Quote::STATUS_DRAFT, Quote::STATUS_SENT, Quote::STATUS_ACCEPTED, Quote::STATUS_REFUSED];

        if (!in_array($newStatus, $validStatuses)) {
            $this->addFlash('danger', 'Statut invalide.');
            return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
        }

        if ($this->isCsrfTokenValid('change_status' . $quote->getId(), $request->getPayload()->getString('_token'))) {
            $quote->setStatus($newStatus);
            $entityManager->flush();

            $this->addFlash('success', 'Statut du devis mis à jour.');
        }

        return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
    }
}
