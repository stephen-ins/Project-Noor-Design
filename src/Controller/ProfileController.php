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
};
