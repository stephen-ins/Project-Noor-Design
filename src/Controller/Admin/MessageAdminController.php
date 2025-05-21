<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class MessageAdminController extends AbstractController
{
    #[Route('/messages', name: 'app_admin_messages')]
    public function index(MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findAllOrderedByDate();
        $unreadCount = $messageRepository->countUnreadMessages();

        return $this->render('admin/admin.messages.html.twig', [
            'messages' => $messages,
            'unreadCount' => $unreadCount
        ]);
    }

    #[Route('/messages/{id}', name: 'app_admin_message_show')]
    public function show(Message $message, EntityManagerInterface $entityManager): Response
    {
        // Marquer le message comme lu s'il ne l'est pas déjà
        if (!$message->isRead()) {
            $message->setIsRead(true);
            $entityManager->flush();
        }

        return $this->render('admin/message.show.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/messages/{id}/delete', name: 'app_admin_message_delete')]
    public function delete(Message $message, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($message);
        $entityManager->flush();

        $this->addFlash('success', 'Le message a été supprimé avec succès.');

        return $this->redirectToRoute('app_admin_messages');
    }

    #[Route('/messages/mark-all-read', name: 'app_admin_messages_mark_all_read')]
    public function markAllAsRead(MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        $unreadMessages = $messageRepository->findBy(['isRead' => false]);

        foreach ($unreadMessages as $message) {
            $message->setIsRead(true);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Tous les messages ont été marqués comme lus.');

        return $this->redirectToRoute('app_admin_messages');
    }
}
