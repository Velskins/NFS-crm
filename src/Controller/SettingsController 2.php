<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/settings')]
class SettingsController extends AbstractController
{
    #[Route('', name: 'app_settings')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_settings_profile');
    }

    #[Route('/profile', name: 'app_settings_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'update_profile') {
                $user->setFirstname($request->request->get('firstname', ''));
                $user->setLastname($request->request->get('lastname', ''));
                $user->setPhone($request->request->get('phone', ''));

                $entityManager->flush();
                $this->addFlash('success', 'Profil mis à jour.');
            }

            if ($action === 'change_password') {
                $current = $request->request->get('current_password', '');
                $new = $request->request->get('new_password', '');
                $confirm = $request->request->get('confirm_password', '');

                if (!$passwordHasher->isPasswordValid($user, $current)) {
                    $this->addFlash('danger', 'Le mot de passe actuel est incorrect.');
                } elseif (strlen($new) < 6) {
                    $this->addFlash('danger', 'Le nouveau mot de passe doit faire au moins 6 caractères.');
                } elseif ($new !== $confirm) {
                    $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
                } else {
                    $user->setPassword($passwordHasher->hashPassword($user, $new));
                    $entityManager->flush();
                    $this->addFlash('success', 'Mot de passe modifié.');
                }
            }

            return $this->redirectToRoute('app_settings_profile');
        }

        return $this->render('settings/index.html.twig', [
            'tab' => 'profile',
        ]);
    }

    #[Route('/billing', name: 'app_settings_billing', methods: ['GET', 'POST'])]
    public function billing(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $user->setCompanyName($request->request->get('company_name', ''));
            $user->setCompanyAddress($request->request->get('company_address', ''));
            $user->setCompanyPostalCode($request->request->get('company_postal_code', ''));
            $user->setCompanyCity($request->request->get('company_city', ''));
            $user->setSiret($request->request->get('siret', ''));
            $user->setTvaNumber($request->request->get('tva_number', ''));

            $entityManager->flush();
            $this->addFlash('success', 'Informations de facturation mises à jour.');
            return $this->redirectToRoute('app_settings_billing');
        }

        return $this->render('settings/index.html.twig', [
            'tab' => 'billing',
        ]);
    }

    #[Route('/notifications', name: 'app_settings_notifications', methods: ['GET', 'POST'])]
    public function notifications(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $user->setNotifEcheance($request->request->getBoolean('notif_echeance'));
            $user->setNotifNewProject($request->request->getBoolean('notif_new_project'));
            $user->setNotifDocumentUploaded($request->request->getBoolean('notif_document_uploaded'));
            $user->setNotifPaymentReceived($request->request->getBoolean('notif_payment_received'));

            $entityManager->flush();
            $this->addFlash('success', 'Préférences de notifications enregistrées.');
            return $this->redirectToRoute('app_settings_notifications');
        }

        return $this->render('settings/index.html.twig', [
            'tab' => 'notifications',
        ]);
    }
}
