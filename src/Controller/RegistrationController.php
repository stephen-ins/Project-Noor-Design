<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationController extends AbstractController
{

    // route pour la page d'inscription
    #[Route('/registration', name: 'app_registration')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {



        // Si l'utilisateur est déjà connecté, on le redirige vers la page d'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Création d'un nouvel utilisateur  
        $user = new Users();
        // Création du formulaire d'inscription
        $form = $this->createForm(RegistrationFormType::class, $user);
        // Traiter la requête pour le formulaire d'inscription
        $form->handleRequest($request);
        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('password')->getData();
            $passwordHash = $userPasswordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($passwordHash);
            // La date de création est déjà définie dans le constructeur de l'entité
            $entityManager->persist($user);
            $entityManager->flush();

            // Ajout d'un message flash de succès
            $this->addFlash('success', 'Votre compte a été créé avec succès !');

            return $this->redirectToRoute('app_home');
        }

        // Si le formulaire n'est pas soumis ou n'est pas valide, on affiche le formulaire d'inscription

        return $this->render('registration/inscription.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
