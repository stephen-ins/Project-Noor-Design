<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\ProfileEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;


#[Route('/profile')]
final class ProfileController extends AbstractController
{
    // route pour la page des profils du client
    #[Route('', name: 'app_profile')]
    public function index(Request $request): Response
    {
        // S'assurer que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'request' => $request,
        ]);
    }

    // route pour supprimer le profil
    #[Route('/delete', name: 'app_profile_delete')]
    public function delete(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // S'assurer que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();

        // Créer un formulaire pour demander le mot de passe de confirmation
        if ($request->isMethod('POST')) {
            $submittedPassword = $request->request->get('password');

            // Vérifier si le mot de passe soumis est valide
            if ($passwordHasher->isPasswordValid($user, $submittedPassword)) {
                $entityManager->remove($user);
                $entityManager->flush();

                // Déconnexion de l'utilisateur
                $this->container->get('security.token_storage')->setToken(null);
                // Invalidation de la session
                $request->getSession()->invalidate();

                // Ajouter un message flash de confirmation
                $this->addFlash('success', 'Votre compte a été supprimé avec succès. Nous sommes désolés de vous voir partir. Nous espérons vous revoir bientôt !');
                return $this->redirectToRoute('app_home');
            } else {
                $this->addFlash('error', 'Mot de passe incorrect. La suppression du compte a été annulée.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->redirectToRoute('app_profile');
    }

    // route pour modifier le profil
    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        // S'assurer que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();


        // Créer le formulaire d'édition de profil
        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer les modifications en base de données
            $entityManager->flush();

            // Ajouter un message flash de confirmation
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            // Rediriger vers la page de profil
            return $this->redirectToRoute('app_profile');
        }
        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    // route pour modifier le mot de passe
    #[Route('/password', name: 'app_profile_password', methods: ['POST'])]
    public function updatePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        // S'assurer que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('update_password', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token CSRF invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_profile');
        }

        // Récupérer les données du formulaire
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        // Vérifier que tous les champs sont remplis
        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            $this->addFlash('error', 'Tous les champs doivent être remplis.');
            return $this->redirectToRoute('app_profile');
        }

        // Vérifier que le mot de passe actuel est correct
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('app_profile');
        }

        // Vérifier que les deux nouveaux mots de passe correspondent
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_profile');
        }

        // Vérifier que le nouveau mot de passe est différent de l'ancien
        if ($currentPassword === $newPassword) {
            $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');
            return $this->redirectToRoute('app_profile');
        }

        // Vérifier que le nouveau mot de passe respecte les critères de sécurité
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.,_\-])[A-Za-z\d@$!%*?&.,_\-]{8,}$/';
        if (!preg_match($regex, $newPassword)) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&.,_-).');
            return $this->redirectToRoute('app_profile');
        }

        // Hacher le nouveau mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        // Enregistrer en base de données
        $entityManager->flush();

        // Ajouter un message flash de succès
        $this->addFlash('success', 'Votre mot de passe a été mis à jour avec succès.');

        return $this->redirectToRoute('app_profile');
    }
};
