<?php

namespace App\Controller;

use App\DTO\Contact;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(): Response
    {
        return $this->render('app/contact.html.twig');
    }

    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer): Response
    {
        $contact = new Contact();
        $contact->setFirstname($request->request->get('firstname'))
            ->setLastname($request->request->get('lastname'))
            ->setEmail($request->request->get('email'))
            ->setPhone($request->request->get('phone'))
            ->setSubject($request->request->get('subject'))
            ->setMessage($request->request->get('message'))
            ->setPrivacy($request->request->has('privacy'));

        // Vérification basique des données
        if (
            empty($contact->getFirstname()) || empty($contact->getLastname()) ||
            empty($contact->getEmail()) || empty($contact->getSubject()) ||
            empty($contact->getMessage()) || !$contact->isPrivacy()
        ) {
            $this->addFlash('error', 'Tous les champs obligatoires doivent être remplis');
            return $this->redirectToRoute('app_contact');
        }

        // Création et envoi de l'email
        $email = (new Email())
            ->from('contact@noordesign.stephen-ins.com')
            ->to('sav.noordesign@gmail.com')
            ->replyTo($contact->getEmail())
            ->subject('Nouveau message de contact: ' . $this->getSubjectLabel($contact->getSubject()))
            ->html($this->renderView('contact/email.html.twig', [
                'contact' => $contact,
                'subjectLabel' => $this->getSubjectLabel($contact->getSubject())
            ]));

        $mailer->send($email);

        // Email de confirmation à l'utilisateur
        $confirmationEmail = (new Email())
            ->from('contact@noordesign.stephen-ins.com')
            ->to($contact->getEmail())
            ->subject('Confirmation de votre message - Noor Design')
            ->html($this->renderView('contact/confirmation.html.twig', [
                'contact' => $contact
            ]));

        $mailer->send($confirmationEmail);

        $this->addFlash('success', 'Votre message a été envoyé avec succès ! Vous recevrez bientôt une confirmation par email.');
        return $this->redirectToRoute('app_contact');
    }

    private function getSubjectLabel(string $subjectValue): string
    {
        $subjects = [
            'commande' => 'Question sur une commande',
            'bijoux' => 'Renseignement sur les bijoux',
            'custom' => 'Demande de création sur-mesure',
            'collaboration' => 'Proposition de collaboration',
            'autre' => 'Autre'
        ];

        return $subjects[$subjectValue] ?? 'Sujet non spécifié';
    }
}
