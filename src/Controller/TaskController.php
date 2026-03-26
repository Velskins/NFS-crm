<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
    #[Route('/task/{id}/toggle', name: 'app_task_toggle', methods: ['POST'])]
    public function toggle(Task $task, EntityManagerInterface $em, TaskRepository $taskRepository): JsonResponse
    {
        // 1. Changement du statut de la tâche
        $currentStatus = strtolower($task->getStatus() ?? '');
        $newStatus = in_array($currentStatus, ['terminé', 'done', 'fini', 'termine']) ? 'en_cours' : 'terminé';
        $task->setStatus($newStatus);
        $em->flush();

        // 2. Recalcul manuel (Anti-bug Doctrine)
        $project = $task->getProject();
        $allTasks = $taskRepository->findBy(['project' => $project]);
        $totalTasks = count($allTasks);

        $doneTasks = 0;
        foreach ($allTasks as $t) {
            $s = strtolower($t->getStatus() ?? '');
            if (in_array($s, ['terminé', 'done', 'fini', 'termine'])) {
                $doneTasks++;
            }
        }

        $progress = ($totalTasks > 0) ? (int)round(($doneTasks / $totalTasks) * 100) : 0;

        // 3. Mise à jour du projet
        $project->setStatus($progress === 100 ? 'terminé' : 'en_cours');
        $em->flush();

        // 4. RÉPONSE (Les noms de clés doivent être identiques au JS)
        return new JsonResponse([
            'progress' => $progress,
            'newStatus' => $task->getStatus(),
            'projectStatus' => $project->getStatus(),
            'doneTasks' => $doneTasks,
            'totalTasks' => $totalTasks
        ]);
    }
}
