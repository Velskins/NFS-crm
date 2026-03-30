<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use App\Repository\PaymentScheduleRepository;
use App\Repository\ProjectRepository;
use App\Repository\QuoteRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_calendar')]
    public function index(): Response
    {
        return $this->render('calendar/index.html.twig');
    }

    #[Route('/calendar/events', name: 'app_calendar_events', methods: ['GET'])]
    public function events(
        TaskRepository            $taskRepository,
        AppointmentRepository     $appointmentRepository,
        ProjectRepository         $projectRepository,
        QuoteRepository           $quoteRepository,
        PaymentScheduleRepository $paymentRepository
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $events = [];

        $appointments = $appointmentRepository->findBy(['user' => $user]);
        foreach ($appointments as $appointment) {
            $events[] = [
                'title' => 'RDV — ' . $appointment->getClient()->getFirstName() . ' ' . $appointment->getClient()->getLastName(),
                'start' => $appointment->getScheduleAt()->format('c'),
                'color' => '#7C3AED', // violet
                'url'   => $this->generateUrl('app_client_show', ['id' => $appointment->getClient()->getId()]),
                'extendedProps' => [
                    'type' => 'appointment',
                    'note' => $appointment->getNote(),
                ],
            ];
        }

        $projects = $projectRepository->findBy(['user' => $user]);
        foreach ($projects as $project) {
            foreach ($project->getTasks() as $task) {
                if ($task->getDeadline()) {
                    $isDone = in_array(strtolower($task->getStatus()), ['terminé', 'done', 'fini', 'termine']);
                    $events[] = [
                        'title' => $task->getTitle(),
                        'start' => $task->getDeadline()->format('Y-m-d'),
                        'color' => $isDone ? '#9CA3AF' : '#3B82F6', // gris si terminé, bleu sinon
                        'url'   => $this->generateUrl('app_project_show', ['id' => $project->getId()]),
                        'extendedProps' => [
                            'type'    => 'task',
                            'status'  => $task->getStatus(),
                            'project' => $project->getTitle(),
                        ],
                    ];
                }
            }
        }

        foreach ($projects as $project) {
            if ($project->getDeadline()) {
                $events[] = [
                    'title' => $project->getTitle(),
                    'start' => $project->getDeadline()->format('Y-m-d'),
                    'color' => '#10B981', // vert
                    'url'   => $this->generateUrl('app_project_show', ['id' => $project->getId()]),
                    'extendedProps' => [
                        'type'   => 'project_deadline',
                        'status' => $project->getStatus(),
                        'client' => $project->getClient()->getCompanyName(),
                    ],
                ];
            }
        }

        $quotes = $quoteRepository->findBy(['user' => $user]);
        foreach ($quotes as $quote) {
            if ($quote->getValidUntil()) {
                $isExpired = $quote->isExpired();
                $events[] = [
                    'title' => 'Devis ' . $quote->getQuoteNumber(),
                    'start' => $quote->getValidUntil()->format('Y-m-d'),
                    'color' => $isExpired ? '#EF4444' : '#F59E0B', // rouge si expiré, orange sinon
                    'url'   => $this->generateUrl('app_quote_show', ['id' => $quote->getId()]),
                    'extendedProps' => [
                        'type'   => 'quote_expiry',
                        'status' => $quote->getStatus(),
                        'client' => $quote->getClient()->getCompanyName(),
                    ],
                ];
            }
        }

        foreach ($projects as $project) {
            foreach ($project->getPaymentSchedules() as $payment) {
                $isPaid = $payment->getStatus() === 'paye';
                $events[] = [
                    'title' => $payment->getAmount() . ' € — ' . $project->getTitle(),
                    'start' => $payment->getDueDate()->format('Y-m-d'),
                    'color' => $isPaid ? '#9CA3AF' : '#EF4444', // gris si payé, rouge sinon
                    'url'   => $this->generateUrl('app_project_show', ['id' => $project->getId()]),
                    'extendedProps' => [
                        'type'   => 'payment',
                        'status' => $payment->getStatus(),
                        'amount' => $payment->getAmount(),
                    ],
                ];
            }
        }

        return new JsonResponse($events);
    }
}
