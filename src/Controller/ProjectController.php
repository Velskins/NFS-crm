<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Document;
use App\Entity\PaymentSchedule;
use App\Entity\Quote;
use App\Entity\Task;
use App\Form\ProjectType;
use App\Form\PaymentScheduleType;
use App\Repository\ProjectRepository;
use App\Repository\QuoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/project')]
final class ProjectController extends AbstractController
{
    #[Route(name: 'app_project_index', methods: ['GET'])]
    public function index(Request $request, ProjectRepository $projectRepository): Response
    {
        $user   = $this->getUser();
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');

        if (!$this->isGranted('ROLE_ADMIN') && $user->getClientProfile()) {
            $projects = $projectRepository->findBy(['client' => $user->getClientProfile()]);
        } else {
            $projects = $projectRepository->findBy(['user' => $user]);
        }

        if ($search) {
            $searchLower = strtolower($search);
            $projects = array_filter($projects, function ($project) use ($searchLower) {
                return str_contains(strtolower($project->getTitle()), $searchLower)
                    || str_contains(strtolower($project->getClient()->getCompanyName()), $searchLower);
            });
        }

        if ($status) {
            $projects = array_filter($projects, function ($project) use ($status) {
                return $project->getStatus() === $status;
            });
        }

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'search'   => $search,
            'status'   => $status,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, QuoteRepository $quoteRepository): Response
    {
        $project = new Project();
        $quote = null;

        // Si on vient d'une conversion de devis → pré-remplir le formulaire
        $quoteId = $request->query->get('quote_id');
        if ($quoteId) {
            $quote = $quoteRepository->find($quoteId);
            if ($quote && $quote->getUser() === $this->getUser() && !$quote->isConverted()) {
                $project->setTitle($quote->getSubject() ?: 'Projet issu du devis ' . $quote->getQuoteNumber());
                $project->setBudget((string) $quote->getTotalAmount());
                $project->setClient($quote->getClient());
                $project->setStatus('en_cours');
            } else {
                $quote = null; // invalide, on ignore
            }
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setUser($this->getUser());
            $project->setCreatedAt(new \DateTimeImmutable());

            // Lier le devis au projet si conversion
            if ($quote) {
                $project->setQuote($quote);
                if ($quote->getStatus() !== Quote::STATUS_ACCEPTED) {
                    $quote->setStatus(Quote::STATUS_ACCEPTED);
                }
            }

            $entityManager->persist($project);
            $entityManager->flush();

            if ($quote) {
                $this->addFlash('success', 'Le devis "' . $quote->getQuoteNumber() . '" a été transformé en projet "' . $project->getTitle() . '".');
            } else {
                $this->addFlash('success', 'Projet "' . $project->getTitle() . '" créé avec succès.');
            }

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form'    => $form,
            'quote'   => $quote,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN')) {
            $clientProfile = $user->getClientProfile();
            if (!$clientProfile || $project->getClient()->getId() !== $clientProfile->getId()) {
                throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce projet.');
            }
        }

        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Projet "' . $project->getTitle() . '" modifié avec succès.');

            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_DELETE', $project);

        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet supprimé avec succès.');
        }

        return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/payment/new', name: 'app_project_payment_new', methods: ['GET', 'POST'])]
    public function newPayment(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        $payment = new PaymentSchedule();
        $payment->setProject($project);
        $form = $this->createForm(PaymentScheduleType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($payment);
            $entityManager->flush();
            $this->addFlash('success', 'Échéance ajoutée !');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/payment_new.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/payment/{id}/delete', name: 'app_project_payment_delete', methods: ['POST'])]
    public function deletePayment(Request $request, PaymentSchedule $payment, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $payment->getProject());

        if ($this->isCsrfTokenValid('delete_payment'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($payment);
            $entityManager->flush();
            $this->addFlash('success', 'Échéance supprimée !');
        }

        return $this->redirectToRoute('app_project_show', ['id' => $payment->getProject()->getId()]);
    }

    #[Route('/{id}/payment/generate', name: 'app_project_payment_generate', methods: ['POST'])]
    public function generatePayments(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        $nombreEcheances    = (int)   $request->request->get('nombre_echeances');
        $montantParEcheance = (float) $request->request->get('montant_echeance');
        $dateDebut          = new \DateTimeImmutable($request->request->get('date_debut'));
        $frequence          = $request->request->get('frequence', 'monthly');

        for ($i = 0; $i < $nombreEcheances; $i++) {
            $payment = new PaymentSchedule();
            $payment->setProject($project);
            $payment->setAmount((string) $montantParEcheance);
            $payment->setStatus('en_attente');
            $payment->setPaidAt(null);

            $interval = $frequence === 'weekly'
                ? new \DateInterval('P' . ($i * 7) . 'D')
                : new \DateInterval('P' . $i . 'M');

            $payment->setDueDate($dateDebut->add($interval));
            $entityManager->persist($payment);
        }

        $entityManager->flush();
        $this->addFlash('success', $nombreEcheances . ' échéances générées !');

        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/document/upload', name: 'app_project_document_upload', methods: ['POST'])]
    public function uploadDocument(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $file = $request->files->get('document');
        $type = $request->request->get('document_type', 'autre');

        if (!$file) {
            $this->addFlash('danger', 'Aucun fichier sélectionné.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $slugger->slug($originalFilename);
        $newFilename      = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/documents',
                $newFilename
            );
        } catch (FileException $e) {
            $this->addFlash('danger', 'Erreur lors de l\'upload du fichier.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $document = new Document();
        $document->setOriginalName($file->getClientOriginalName() ?: $originalFilename);
        $document->setFilePath('uploads/documents/' . $newFilename);
        $document->setType($type);
        $document->setProject($project);
        $document->setUploadedBy($this->getUser());

        $entityManager->persist($document);
        $entityManager->flush();

        $this->addFlash('success', 'Document "' . $document->getOriginalName() . '" ajouté.');
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/task/{id}/toggle', name: 'app_task_toggle', methods: ['POST'])]
    public function toggleTask(Task $task, EntityManagerInterface $entityManager): Response
    {
        $newTaskStatus = ($task->getStatus() === 'terminé') ? 'à faire' : 'terminé';
        $task->setStatus($newTaskStatus);

        $project = $task->getProject();
        $entityManager->flush();

        if ($project->getProgress() === 100 && strtolower($project->getStatus()) === 'en_cours') {
            $project->setStatus('livre');
            $entityManager->flush();
        } elseif ($project->getProgress() < 100 && $project->getStatus() === 'livre') {
            $project->setStatus('en_cours');
            $entityManager->flush();
        }

        return $this->json([
            'newStatus'     => $newTaskStatus,
            'projectStatus' => $project->getStatus(),
            'progress'      => $project->getProgress(),
        ]);
    }

    // --- NOUVELLE MÉTHODE DE PAIEMENT ---
    #[Route('/payment/{id}/pay', name: 'app_payment_pay', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function markAsPaid(PaymentSchedule $payment, EntityManagerInterface $em): JsonResponse
    {
        $payment->setStatus('paye');
        $payment->setPaidAt(new \DateTimeImmutable());
        $em->flush();

        $project = $payment->getProject();
        $totalPaye = 0;
        foreach ($project->getPaymentSchedules() as $schedule) {
            if ($schedule->getStatus() === 'paye') {
                $totalPaye += (float) $schedule->getAmount();
            }
        }

        $budget = (float) $project->getBudget();
        $restant = $budget - $totalPaye;
        $client = $project->getClient();
        $newArgentEnAttente = $client ? $client->getArgentEnAttente() : null;

        return new JsonResponse([
            'success' => true,
            'paidAt' => $payment->getPaidAt()->format('d/m/Y'),
            'newTotalPaye' => number_format($totalPaye, 2, '.', ',') . ' €',
            'newRestant' => number_format($restant, 2, '.', ',') . ' €',
            'newArgentEnAttenteRaw' => $newArgentEnAttente,
            'newArgentEnAttente' => $newArgentEnAttente !== null
                ? number_format($newArgentEnAttente, 2, '.', ',') . ' €'
                : null,
        ]);
    }
}
