<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\ProfileEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile')]
final class ProfileController extends AbstractController
{
    // route pour la page profil
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
}
