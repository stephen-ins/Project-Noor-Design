<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Categories;
use App\Entity\Products;
use App\Form\ProductsFormType;
use App\Repository\ProductsRepository;
use App\Repository\UsersRepository;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin', name: 'app_admin_')]
final class AdminController extends AbstractController
{
    // route pour la page d'administration --> main
    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/admin.index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    // route pour la gestion des produits (insertion, modification, suppression)
    #[Route('/products', name: 'products')]
    public function products(?Products $product, EntityManagerInterface $entityManager, Request $request, SluggerInterface $slugger, ProductsRepository $productsRepository, CategoriesRepository $categoriesRepository): Response
    {
        // INSERTION les produits dans la base de données via un formulaire modale "ajouter un produit"
        if (!$product) {
            $product = new Products();
            $product->setDateAjout(new \DateTimeImmutable()); // Définir la date d'ajout pour un nouveau produit
        }

        $formProduct = $this->createForm(ProductsFormType::class, $product);
        $formProduct->handleRequest($request);

        if ($formProduct->isSubmitted() && $formProduct->isValid()) {
            // Gérer l'upload d'image principale
            if ($formProduct->has('image')) {
                $imageFile = $formProduct->get('image')->getData();

                if ($imageFile) {
                    // Nom de fichier d orgine sans extension
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    // Securiser le nom du fichier (supression espace etc... )
                    $safeFilename = $slugger->slug($originalFilename);
                    // On renomme l'image
                    $newFilename = 'noor-' . $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                    // Repertoire de destination
                    $currentPath = $this->getParameter('products_images_directory');

                    try {
                        // le try va tenter de copier l'image
                        $imageFile->move($currentPath, $newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur s\'est produite lors de l\'upload de l\'image principale: ' . $e->getMessage());
                    }

                    // Mise à jour de l'entité produit avec le nom de l'image
                    $product->setImage($newFilename);
                }
            }

            // Gérer les images additionnelles
            if ($formProduct->has('additionalImages')) {
                $additionalImages = [];
                $additionalImageFiles = $formProduct->get('additionalImages')->getData();

                if ($additionalImageFiles) {
                    $currentPath = $this->getParameter('products_images_directory');

                    foreach ($additionalImageFiles as $additionalImageFile) {
                        $originalFilename = pathinfo($additionalImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = 'noor-' . $safeFilename . '-' . uniqid() . '.' . $additionalImageFile->guessExtension();

                        try {
                            // Déplacer le fichier vers le répertoire de destination
                            $additionalImageFile->move($currentPath, $newFilename);

                            // Ajouter le nom du fichier au tableau des images additionnelles
                            $additionalImages[] = $newFilename;
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Une erreur s\'est produite lors de l\'upload d\'une image additionnelle: ' . $e->getMessage());
                        }
                    }

                    // Mise à jour de l'entité produit avec les images additionnelles
                    $product->setAdditionalImages($additionalImages);
                }
            }

            // Traitement du champ statut (non mappé directement)
            if ($formProduct->has('statut')) {
                $statut = $formProduct->get('statut')->getData();
                // Si le statut est défini à false (rupture de stock), mettre le stock à 0
                if ($statut === false) {
                    $product->setStock(0);
                }
            }

            // Persistance et sauvegarde
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été enregistré avec succès.');

            // Redirection pour éviter le problème de resoumission du formulaire
            return $this->redirectToRoute('app_admin_products');
        }

        return $this->render('admin/admin.products.html.twig', [
            'controller_name' => 'AdminController',
            'products' => $productsRepository->findAll(),
            'categories' => $categoriesRepository->findAll(),
            'product' => $product,
            'formProduct' => $formProduct->createView(), // Passer le formulaire à la vue
        ]);
    }

    // route pour la gestion des commandes
    #[Route('/orders', name: 'orders')]
    public function orders(): Response
    {
        return $this->render('admin/admin.orders.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    // route pour la gestion des catégories     
    #[Route('/categories', name: 'categories')]
    public function categories(): Response
    {
        return $this->render('admin/admin.categories.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    // route pour la gestion des clients
    #[Route('/users', name: 'users')]
    public function users(UsersRepository $usersRepository): Response
    {
        $users = $usersRepository->findAll();

        return $this->render('admin/admin.users.html.twig', [
            'users' => $users,
        ]);
    }

    // route pour modifier les rôles d'un utilisateur
    #[Route('/users/{id}/edit', name: 'users_edit')]
    public function editUser(Request $request, Users $user, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $roles = $request->request->all()['roles'] ?? [];
            if (!is_array($roles)) {
                $roles = [$roles];
            }
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

    // route pour la gestion des commentaires
    #[Route('/comments', name: 'comments')]
    public function comments(): Response
    {
        return $this->render('admin/admin.comments.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    // route pour la gestion des messages boite de contact
    #[Route('/messages', name: 'messages')]
    public function messages(): Response
    {
        return $this->render('admin/admin.messages.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
