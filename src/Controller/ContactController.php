<?php

namespace App\Controller;

use App\DTO\Contact;
use App\Entity\Message;
use App\Form\ContactType;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ContactController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function index(): Response
    {
        $contact = new Contact();
        $formContact = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('app_contact_submit'),
            'method' => 'POST',
        ]);


        return $this->render('app/contact.html.twig', [
            'formContact' => $formContact->createView(),
        ]);
    }

    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer, MessageRepository $messageRepository): Response
    {
        $contact = new Contact();
        $formContact = $this->createForm(ContactType::class, $contact);
        $formContact->handleRequest($request);

        if ($formContact->isSubmitted() && $formContact->isValid()) {
            try {
                // Sauvegarder le message en base de données
                $message = new Message();
                $message->setFirstname($contact->getFirstname());
                $message->setLastname($contact->getLastname());
                $message->setEmail($contact->getEmail());
                $message->setPhone($contact->getPhone());
                $message->setSubject($contact->getSubject());
                $message->setMessage($contact->getMessage());

                $messageRepository->save($message, true);

                // Création et envoi de l'email
                $email = (new Email())
                    ->from('contact@noordesign.stephen-ins.com')
                    ->to('contact@noordesign.stephen-ins.com')
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
                        'contact' => $contact,
                        'subjectLabel' => $this->getSubjectLabel($contact->getSubject())
                    ]));

                $mailer->send($confirmationEmail);

                $this->addFlash('success', 'Votre message a été envoyé avec succès ! Vous recevrez bientôt une confirmation par email.');
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur s\'est produite lors de l\'envoi de votre message. Veuillez réessayer ultérieurement.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur générale lors du traitement du formulaire de contact: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur s\'est produite lors du traitement de votre demande. Veuillez réessayer ultérieurement.');
            }
        } else if ($formContact->isSubmitted()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez vérifier vos informations.');
        }

        return $this->redirectToRoute('app_contact');
    }

    private function getSubjectLabel(string $subjectValue): string
    {
        $subjects = [
            'commande' => 'Question sur une commande',
            'bijoux' => 'Renseignement sur les bijoux',
            'custom' => 'Demande de création sur-mesure',
            'collaboration' => 'Proposition de collaboration',
            'autre' => 'Autre demande'
        ];

        return $subjects[$subjectValue] ?? 'Demande non spécifiée';
    }
}
