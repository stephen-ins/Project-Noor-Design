<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'app_admin_')]
final class AdminController extends AbstractController
{
    // route pour la page d'administration --> main
    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/admin.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    // route pour la gestion des utilisateurs
    #[Route('/users', name: 'users')]
    public function users(UsersRepository $usersRepository): Response
    {
        $users = $usersRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    // route pour modifier les rôles d'un utilisateur
    #[Route('/users/{id}/edit', name: 'users_edit')]
    public function editUser(Request $request, Users $user, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $roles = $request->request->get('roles', []);
            $user->setRoles($roles);
            $entityManager->flush();

            $this->addFlash('success', 'Les rôles de l\'utilisateur ont été mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users_edit.html.twig', [
            'user' => $user,
        ]);
    }

    // route pour supprimer un utilisateur
    #[Route('/users/{id}/delete', name: 'users_delete', methods: ['POST'])]
    public function deleteUser(Request $request, Users $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_users', [], Response::HTTP_SEE_OTHER);
    }
}
