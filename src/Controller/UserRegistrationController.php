<?php

namespace App\Controller;

use App\Entity\Users;
use App\Security\EmailVerifier;
use Symfony\Component\Mime\Address;
use App\Form\UserRegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class UserRegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    // route pour la page d'inscription
    #[Route('/user-registration', name: 'app_user_registration')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // Si l'utilisateur est déjà connecté, on le redirige vers la page d'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Création d'un nouvel utilisateur  
        $user = new Users();
        // Création du formulaire d'inscription
        $form = $this->createForm(UserRegistrationFormType::class, $user);
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

            // Définir l'utilisateur comme non vérifié
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // Envoi de l'email de vérification
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('contact@noordesign.stephen-ins.com', 'Noor Design'))
                    ->to($user->getEmail())
                    ->subject('Confirmez votre adresse email - Noor Design')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
                    ->context([
                        'name' => $user->getPrenom() . ' ' . $user->getNom()
                    ])
            );

            // Ajout d'un message flash de succès
            $this->addFlash('success', 'Votre compte a été créé avec succès ! Un email de vérification vous a été envoyé. Veuillez vérifier votre boite de réception ainsi que vos spams.');

            // Redirection vers la page de connexion
            return $this->redirectToRoute('app_login');
        }

        // Si le formulaire n'est pas soumis ou n'est pas valide, on affiche le formulaire d'inscription

        return $this->render('registration/inscription.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'ID directement depuis la requête
        $id = $request->query->get('id');
        if (!$id) {
            $this->addFlash('error', 'Lien de vérification incorrect. Aucun identifiant utilisateur trouvé.');
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(Users::class)->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_login');
        }

        // Si l'utilisateur est déjà vérifié
        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre adresse email est déjà vérifiée. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Valide le lien de confirmation d'email, définit User::isVerified=true et persiste
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_login');
        }

        // Message de succès
        $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès. Vous pouvez maintenant profiter pleinement des services de Noor Design.');

        // Redirection vers la page de connexion plutôt que la page d'accueil
        return $this->redirectToRoute('app_login');
    }

    #[Route('/verify/resend', name: 'app_verify_resend_email')]
    public function resendVerifyEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $email = $request->query->get('email');

        // Si l'utilisateur est connecté, on utilise ses informations
        $user = $this->getUser();

        // Si l'utilisateur n'est pas connecté mais qu'un email est fourni
        if (!$user && $email) {
            $user = $entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'Aucun compte trouvé avec cette adresse email.');
                return $this->redirectToRoute('app_login');
            }
        } elseif (!$user) {
            $this->addFlash('error', 'Vous devez être connecté ou fournir une adresse email pour demander un nouvel email de vérification.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('success', 'Votre adresse email est déjà vérifiée. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        // Envoi d'un nouvel email de vérification
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('contact@noordesign.stephen-ins.com', 'Noor Design'))
                ->to($user->getEmail())
                ->subject('Confirmez votre adresse email - Noor Design')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context([
                    'name' => $user->getPrenom() . ' ' . $user->getNom()
                ])
        );

        $this->addFlash('success', 'Un nouvel email de vérification a été envoyé. Veuillez vérifier votre boite de réception.');
        return $this->redirectToRoute('app_login');
    }
}
