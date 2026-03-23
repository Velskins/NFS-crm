<?php
namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\PaymentSchedule;
use App\Form\PaymentScheduleType;

#[Route('/project')]
final class ProjectController extends AbstractController
{
    #[Route(name: 'app_project_index', methods: ['GET'])]
    public function index(Request $request, ProjectRepository $projectRepository): Response
    {
        $user = $this->getUser();

        // ROLE_CLIENT : ne voit que les projets liés à son profil client
        if (!$this->isGranted('ROLE_ADMIN') && $user->getClientProfile()) {
            $projects = $projectRepository->findBy(['client' => $user->getClientProfile()]);
        } else {
            $projects = $projectRepository->findBy(['user' => $user]);
        }

        // Filtrer par statut si demandé
        $filter = $request->query->get('filter', 'tout');
        if ($filter !== 'tout') {
            $statusMap = [
                'en_cours'   => ['en cours'],
                'en_attente' => ['en attente'],
                'fini'       => ['fini', 'terminé'],
                'annule'     => ['annulé', 'annule'],
            ];
            $allowedStatuses = $statusMap[$filter] ?? [];
            if ($allowedStatuses) {
                $projects = array_filter($projects, function ($project) use ($allowedStatuses) {
                    return in_array(strtolower($project->getStatus()), $allowedStatuses);
                });
            }
        }

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setUser($this->getUser());
            $project->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($project);
            $entityManager->flush();

            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

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

            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_DELETE', $project);

        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();
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

        $nombreEcheances = (int) $request->request->get('nombre_echeances');
        $montantParEcheance = (float) $request->request->get('montant_echeance');
        $dateDebut = new \DateTimeImmutable($request->request->get('date_debut'));
        $frequence = $request->request->get('frequence', 'monthly'); // monthly ou weekly

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
}
