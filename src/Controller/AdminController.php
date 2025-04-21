<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Orders;
use App\Entity\Products;
use App\Entity\Categories;
use App\Form\CategoryFormType;
use App\Form\ProductsFormType;
use App\Repository\UsersRepository;
use App\Repository\ProductsRepository;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    // route pour la gestion des produits (liste et suppression)
    #[Route('/products', name: 'products')]
    public function products(ProductsRepository $productsRepository, CategoriesRepository $categoriesRepository): Response
    {
        // Récupération de tous les produits
        $products = $productsRepository->findAll();

        // Création d'un nouveau produit pour le formulaire d'ajout
        $newProduct = new Products();
        $newProduct->setDateAjout(new \DateTimeImmutable());

        // Création du formulaire d'ajout
        $formProduct = $this->createForm(ProductsFormType::class, $newProduct);

        return $this->render('admin/admin.products.html.twig', [
            'controller_name' => 'AdminController',
            'products' => $products,
            'categories' => $categoriesRepository->findAll(),
            'formProduct' => $formProduct->createView(),
        ]);
    }

    // route pour ajouter un produit
    #[Route('/products/add', name: 'products_add', methods: ['POST'])]
    public function addProduct(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Création d'un nouveau produit
        $product = new Products();
        $product->setDateAjout(new \DateTimeImmutable());

        // Création du formulaire
        $form = $this->createForm(ProductsFormType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image principale
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = 'noor-' . $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('products_images_directory'), $newFilename);
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur s\'est produite lors de l\'upload de l\'image: ' . $e->getMessage());
                    return $this->redirectToRoute('app_admin_products');
                }
            } else {
                // Image requise pour un nouveau produit
                $this->addFlash('error', 'Une image principale est requise pour ajouter un produit.');
                return $this->redirectToRoute('app_admin_products');
            }

            // Gestion des images additionnelles
            $additionalImageFiles = $form->get('additionalImages')->getData();
            if ($additionalImageFiles && count($additionalImageFiles) > 0) {
                $additionalImages = [];

                foreach ($additionalImageFiles as $additionalImageFile) {
                    $originalFilename = pathinfo($additionalImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = 'noor-' . $safeFilename . '-' . uniqid() . '.' . $additionalImageFile->guessExtension();

                    try {
                        $additionalImageFile->move($this->getParameter('products_images_directory'), $newFilename);
                        $additionalImages[] = $newFilename;
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur s\'est produite lors de l\'upload d\'une image additionnelle: ' . $e->getMessage());
                    }
                }

                if (count($additionalImages) > 0) {
                    $product->setAdditionalImages($additionalImages);
                }
            }

            $entityManager->persist($product);
            $entityManager->flush();
            $this->addFlash('success', 'Le produit a été ajouté avec succès.');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_admin_products');
    }

    // route pour l'édition d'un produit
    #[Route('/products/edit/{id}', name: 'products_edit', methods: ['GET', 'POST'])]
    public function editProduct(Request $request, Products $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Sauvegarder l'image actuelle et les images additionnelles
        $currentImage = $product->getImage();
        $currentAdditionalImages = $product->getAdditionalImages();

        // Création du formulaire d'édition
        $form = $this->createForm(ProductsFormType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image principale
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = 'noor-' . $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('products_images_directory'), $newFilename);
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur s\'est produite lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            } else {
                // Conserver l'image actuelle si aucune nouvelle image n'est fournie
                $product->setImage($currentImage);
            }

            // Gestion des images additionnelles
            $additionalImageFiles = $form->get('additionalImages')->getData();
            if ($additionalImageFiles && count($additionalImageFiles) > 0) {
                $additionalImages = [];

                foreach ($additionalImageFiles as $additionalImageFile) {
                    $originalFilename = pathinfo($additionalImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = 'noor-' . $safeFilename . '-' . uniqid() . '.' . $additionalImageFile->guessExtension();

                    try {
                        $additionalImageFile->move($this->getParameter('products_images_directory'), $newFilename);
                        $additionalImages[] = $newFilename;
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur s\'est produite lors de l\'upload d\'une image additionnelle: ' . $e->getMessage());
                    }
                }

                if (count($additionalImages) > 0) {
                    $product->setAdditionalImages($additionalImages);
                }
            } else {
                // Conserver les images additionnelles actuelles si aucune nouvelle image n'est fournie
                $product->setAdditionalImages($currentAdditionalImages);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Le produit a été modifié avec succès.');

            return $this->redirectToRoute('app_admin_products');
        }

        // Si la requête est en GET, afficher le formulaire d'édition
        return $this->render('admin/edit.product.html.twig', [
            'product' => $product,
            'formProduct' => $form->createView(),
        ]);
    }

    // route pour supprimer un produit
    #[Route('/products/delete', name: 'products_delete', methods: ['POST'])]
    public function deleteProduct(Request $request, EntityManagerInterface $entityManager, ProductsRepository $productsRepository): Response
    {
        // Récupérer l'ID du produit à supprimer depuis la requête
        $productId = $request->query->get('id');
        $product = $productsRepository->find($productId);

        if (!$product) {
            $this->addFlash('error', 'Le produit demandé n\'existe pas.');
            return $this->redirectToRoute('app_admin_products');
        }

        // Vérifier le jeton CSRF
        if ($this->isCsrfTokenValid('delete_product', $request->request->get('_token'))) {
            try {
                // Supprimer le produit
                $entityManager->remove($product);
                $entityManager->flush();

                $this->addFlash('success', 'Le produit a été supprimé avec succès.');
            } catch (\Exception $e) {
                // Si une contrainte d'intégrité est violée (par exemple, le produit est utilisé par des commandes)
                $this->addFlash('error', 'Impossible de supprimer ce produit car il est utilisé par des commandes.');
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_admin_products');
    }

    // route pour la gestion des catégories (insertion, modification)    
    #[Route('/categories', name: 'categories')]
    public function categories(Request $request, CategoriesRepository $categoriesRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération de toutes les catégories
        $categories = $categoriesRepository->findAll();

        // Récupération de l'ID de la catégorie à modifier (si présent)
        $editCategoryId = $request->query->get('edit');

        // Pour l'édition d'une catégorie existante
        if ($editCategoryId) {
            $categoryToEdit = $categoriesRepository->find($editCategoryId);
            if (!$categoryToEdit) {
                $this->addFlash('error', 'La catégorie demandée n\'existe pas.');
                return $this->redirectToRoute('app_admin_categories');
            }
            $formCategory = $this->createForm(CategoryFormType::class, $categoryToEdit);
        } else {
            // Pour l'ajout d'une nouvelle catégorie
            $categoryToEdit = new Categories();
            $formCategory = $this->createForm(CategoryFormType::class, $categoryToEdit);
        }

        $formCategory->handleRequest($request);

        if ($formCategory->isSubmitted() && $formCategory->isValid()) {
            // Si c'est une nouvelle catégorie, définir la date de création
            if (!$categoryToEdit->getId()) {
                $categoryToEdit->setDateCreation(new \DateTime());
                $entityManager->persist($categoryToEdit);
                $message = 'La catégorie a été ajoutée avec succès.';
            } else {
                $message = 'La catégorie a été modifiée avec succès.';
            }

            $entityManager->flush();
            $this->addFlash('success', $message);

            // Redirection explicite vers la route sans paramètre d'édition
            return $this->redirectToRoute('app_admin_categories', [], Response::HTTP_SEE_OTHER);
        }

        // Ajout d'un bouton pour réinitialiser le formulaire
        $newCategoryButton = true;

        return $this->render('admin/admin.categories.html.twig', [
            'controller_name' => 'AdminController',
            'categories' => $categories,
            'formCategory' => $formCategory->createView(),
            'editCategory' => $editCategoryId ? $categoryToEdit : null,
            'newCategoryButton' => $newCategoryButton,
        ]);
    }

    // Route pour supprimer une catégorie
    #[Route('/categories/{id}/delete', name: 'categories_delete', methods: ['POST'])]
    public function deleteCategory(Request $request, Categories $category, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le jeton CSRF
        if ($this->isCsrfTokenValid('delete_category', $request->request->get('_token'))) {
            try {
                // Supprimer la catégorie
                $entityManager->remove($category);
                $entityManager->flush();

                $this->addFlash('success', 'La catégorie a été supprimée avec succès.');
            } catch (\Exception $e) {
                // Si une contrainte d'intégrité est violée (par exemple, la catégorie est utilisée par des produits)
                $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle est utilisée par des produits.');
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_admin_categories');
    }


    // route pour la gestion des commandes
    #[Route('/orders', name: 'orders')]
    public function orders(EntityManagerInterface $entityManager): Response
    {
        $orders = $entityManager->getRepository(Orders::class)->findBy([], ['date_commande' => 'DESC']);

        return $this->render('admin/admin.orders.html.twig', [
            'controller_name' => 'AdminController',
            'orders' => $orders
        ]);
    }

    // route pour voir le détail d'une commande
    #[Route('/orders/{id}', name: 'orders_detail')]
    public function orderDetail(Orders $order): Response
    {
        return $this->render('admin/admin.order.detail.html.twig', [
            'controller_name' => 'AdminController',
            'order' => $order
        ]);
    }

    // route pour mettre à jour le statut d'une commande
    #[Route('/orders/{id}/status', name: 'orders_update_status', methods: ['POST'])]
    public function updateOrderStatus(Request $request, Orders $order, EntityManagerInterface $entityManager): Response
    {
        $newStatus = $request->request->get('status');

        if ($newStatus && in_array($newStatus, OrderStatus::getValues())) {
            $order->setStatus(OrderStatus::from($newStatus));
            $entityManager->flush();

            $this->addFlash('success', 'Le statut de la commande a été mis à jour');
        } else {
            $this->addFlash('error', 'Statut invalide');
        }

        return $this->redirectToRoute('app_admin_orders_detail', ['id' => $order->getId()]);
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
