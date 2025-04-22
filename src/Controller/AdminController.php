<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Orders;
use App\Entity\Products;
use App\Entity\Categories;
use App\Enum\OrderStatus;
use App\Form\CategoryFormType;
use App\Form\ProductsFormType;
use App\Repository\UsersRepository;
use App\Repository\ProductsRepository;
use App\Repository\CategoriesRepository;
use App\Repository\OrdersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_index')]
    public function index(
        OrdersRepository $ordersRepository,
        ProductsRepository $productsRepository,
        UsersRepository $UsersRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Statistiques des ventes (montant total)
        $totalSales = $ordersRepository->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Statistiques de ce mois
        $startOfMonth = new \DateTime('first day of this month');
        $salesThisMonth = $ordersRepository->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->where('o.date_commande >= :start')
            ->setParameter('start', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Comparaison avec le mois précédent pour calculer la variation
        $startOfLastMonth = (clone $startOfMonth)->modify('-1 month');
        $salesLastMonth = $ordersRepository->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->where('o.date_commande >= :start AND o.date_commande < :end')
            ->setParameter('start', $startOfLastMonth)
            ->setParameter('end', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Calculer la variation en pourcentage
        $salesVariation = 0;
        if ($salesLastMonth > 0) {
            $salesVariation = round((($salesThisMonth - $salesLastMonth) / $salesLastMonth) * 100);
        }

        // Nombre total de commandes
        $totalOrders = $ordersRepository->count([]);

        // Commandes en attente (statut "en cours")
        $pendingOrders = $ordersRepository->count(['status' => OrderStatus::EN_ATTENTE]);

        // Nombre total de clients
        $totalCustomers = $UsersRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->getQuery()
            ->getSingleScalarResult();

        // Nouveaux clients ce mois
        $newCustomers = $UsersRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.creation_date >= :start')
            ->setParameter('start', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Nombre total de produits
        $totalProducts = $productsRepository->count([]);

        // Produits en rupture de stock
        $outOfStockProducts = $productsRepository->count(['stock' => 0]);

        // Récupérer les commandes récentes
        $recentOrders = $ordersRepository->findBy([], ['date_commande' => 'DESC'], 5);

        // Récupérer les produits les plus vendus
        $popularProducts = $entityManager->createQuery(
            'SELECT p, SUM(od.quantity) as total
            FROM App\Entity\Products p
            JOIN App\Entity\OrderDetails od WITH od.product = p
            GROUP BY p.id
            ORDER BY total DESC'
        )->setMaxResults(5)->getResult();

        // Récupérer les produits avec stock faible
        $lowStockProducts = $productsRepository->findBy(
            ['stock' => [1, 2, 3, 4, 5]],
            ['stock' => 'ASC'],
            10
        );

        // Données pour le graphique des ventes (derniers 6 mois)
        $salesChartData = [];
        $salesChartLabels = [];

        // Créer des données pour les 6 derniers mois
        $currentDate = new \DateTime();
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = (clone $currentDate)->modify("-$i month");
            $monthStart = (clone $monthDate)->modify('first day of this month')->setTime(0, 0);
            $monthEnd = (clone $monthDate)->modify('last day of this month')->setTime(23, 59, 59);

            $monthlySales = $ordersRepository->createQueryBuilder('o')
                ->select('SUM(o.total)')
                ->where('o.date_commande BETWEEN :start AND :end')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;

            $salesChartData[] = $monthlySales;
            $salesChartLabels[] = $monthDate->format('M Y');
        }

        // Récupérer les activités récentes (commandes, inscriptions, etc.)
        $recentActivities = [];

        // Dernières commandes (max 20)
        $latestOrders = $ordersRepository->findBy([], ['date_commande' => 'DESC'], 20);
        foreach ($latestOrders as $order) {
            $recentActivities[] = [
                'icon' => 'bi-bag',
                'description' => sprintf(
                    'Commande #CMD-%d de %s %s (%s€)',
                    $order->getId(),
                    $order->getUser()->getNom(),
                    $order->getUser()->getPrenom(),
                    number_format($order->getTotal(), 2, ',', ' ')
                ),
                'date' => $order->getDateCommande()
            ];
        }

        // Derniers utilisateurs inscrits (max 20)
        $latestUsers = $UsersRepository->findBy([], ['creation_date' => 'DESC'], 20);
        foreach ($latestUsers as $user) {
            $recentActivities[] = [
                'icon' => 'bi-person-plus',
                'description' => sprintf(
                    'Nouvel utilisateur: %s %s',
                    $user->getNom(),
                    $user->getPrenom()
                ),
                'date' => $user->getCreationDate()
            ];
        }

        // Derniers produits ajoutés (max 20)
        $latestProducts = $productsRepository->findBy([], ['date_ajout' => 'DESC'], 20);
        foreach ($latestProducts as $product) {
            $recentActivities[] = [
                'icon' => 'bi-box-seam',
                'description' => sprintf('Nouveau produit: %s', $product->getNom()),
                'date' => $product->getDateAjout()
            ];
        }

        // Trier les activités par date (les plus récentes d'abord)
        usort($recentActivities, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        // Limiter à 20 activités maximum
        $recentActivities = array_slice($recentActivities, 0, 20);

        return $this->render('admin/admin.index.html.twig', [
            'totalSales' => $totalSales,
            'salesThisMonth' => $salesThisMonth,
            'salesVariation' => $salesVariation,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'totalCustomers' => $totalCustomers,
            'newCustomers' => $newCustomers,
            'totalProducts' => $totalProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'recentOrders' => $recentOrders,
            'popularProducts' => $popularProducts,
            'lowStockProducts' => $lowStockProducts,
            'salesChartData' => $salesChartData,
            'salesChartLabels' => $salesChartLabels,
            'recentActivities' => $recentActivities,
        ]);
    }

    // route pour la gestion des produits (liste et suppression)
    #[Route('/products', name: 'app_admin_products')]
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
    #[Route('/products/add', name: 'app_admin_products_add', methods: ['POST'])]
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
    #[Route('/products/edit/{id}', name: 'app_admin_products_edit', methods: ['GET', 'POST'])]
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
    #[Route('/products/delete', name: 'app_admin_products_delete', methods: ['POST'])]
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
    #[Route('/categories', name: 'app_admin_categories')]
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
    #[Route('/categories/{id}/delete', name: 'app_admin_categories_delete', methods: ['POST'])]
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
    #[Route('/orders', name: 'app_admin_orders')]
    public function orders(EntityManagerInterface $entityManager): Response
    {
        $orders = $entityManager->getRepository(Orders::class)->findBy([], ['date_commande' => 'DESC']);

        return $this->render('admin/admin.orders.html.twig', [
            'controller_name' => 'AdminController',
            'orders' => $orders
        ]);
    }

    // route pour voir le détail d'une commande
    #[Route('/orders/{id}', name: 'app_admin_order_show')]
    public function orderDetail(Orders $order): Response
    {
        return $this->render('admin/admin.order.detail.html.twig', [
            'controller_name' => 'AdminController',
            'order' => $order
        ]);
    }

    // route pour mettre à jour le statut d'une commande
    #[Route('/orders/{id}/status', name: 'app_admin_orders_update_status', methods: ['POST'])]
    public function updateOrderStatus(Request $request, Orders $order, EntityManagerInterface $entityManager): Response
    {
        $newStatus = $request->request->get('status');

        try {
            // Tenter de créer une instance de l'énumération à partir de la valeur
            $orderStatus = OrderStatus::from($newStatus);
            $order->setStatus($orderStatus);
            $entityManager->flush();

            $this->addFlash('success', 'Le statut de la commande a été mis à jour');
        } catch (\ValueError $e) {
            // En cas de valeur invalide
            $this->addFlash('error', 'Statut invalide');
        }

        return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
    }



    // route pour la gestion des clients
    #[Route('/users', name: 'app_admin_users')]
    public function users(UsersRepository $usersRepository): Response
    {
        $users = $usersRepository->findAll();

        return $this->render('admin/admin.users.html.twig', [
            'users' => $users,
        ]);
    }

    // route pour modifier les rôles d'un utilisateur
    #[Route('/users/{id}/edit', name: 'app_admin_users_edit')]
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
    #[Route('/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
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
    #[Route('/comments', name: 'app_admin_comments')]
    public function comments(): Response
    {
        return $this->render('admin/admin.comments.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    // route pour la gestion des messages boite de contact
    #[Route('/messages', name: 'app_admin_messages')]
    public function messages(): Response
    {
        return $this->render('admin/admin.messages.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
