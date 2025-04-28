<?php

namespace App\Controller;

use App\Entity\Products;
use App\Repository\CategoriesRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\WishlistRepository;

final class IndexController extends AbstractController
{

    // route pour la page d'accueil
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('app/index.html.twig', [
            'controller_name' => 'Home',
        ]);
    }

    // route pour la page à propos
    #[Route('/about_me', name: 'app_about_me')]
    public function aboutMe(): Response
    {
        return $this->render('app/about_me.html.twig', [
            'controller_name' => 'À propos',
        ]);
    }

    // route pour la page catalogue
    #[Route('/catalogue', name: 'app_catalogue')]
    public function catalogue(ProductsRepository $repoAllProducts, CategoriesRepository $categoriesRepository, WishlistRepository $wishlistRepository, Request $request): Response
    {
        // Récupérer les paramètres de filtrage
        $categoryId = $request->query->get('category');
        $priceRange = $request->query->get('price_range');
        $sort = $request->query->get('sort', 'nouveautes'); // Valeur par défaut: nouveautés
        $wishlistOnly = $request->query->get('wishlist_only') === '1';

        // Construire les critères de recherche
        $criteria = [];
        if ($categoryId) {
            $criteria['categorie'] = $categoryId;
        }

        // Appliquer les filtres de prix
        $qb = $repoAllProducts->createQueryBuilder('p');

        if (!empty($criteria)) {
            foreach ($criteria as $field => $value) {
                $qb->andWhere("p.{$field} = :{$field}")
                    ->setParameter($field, $value);
            }
        }

        // Filtre de prix
        if ($priceRange) {
            switch ($priceRange) {
                case '0-50':
                    $qb->andWhere('p.prix < 50');
                    break;
                case '50-100':
                    $qb->andWhere('p.prix >= 50 AND p.prix <= 100');
                    break;
                case '100-200':
                    $qb->andWhere('p.prix > 100 AND p.prix <= 200');
                    break;
                case '200+':
                    $qb->andWhere('p.prix > 200');
                    break;
            }
        }

        // Tri
        switch ($sort) {
            case 'prix-asc':
                $qb->orderBy('p.prix', 'ASC');
                break;
            case 'prix-desc':
                $qb->orderBy('p.prix', 'DESC');
                break;
            case 'nouveautes':
            default:
                $qb->orderBy('p.id', 'DESC'); // Supposons que les ID plus élevés sont les plus récents
                break;
        }

        // Exécuter la requête
        $allProducts = $qb->getQuery()->getResult();

        // Récupérer toutes les catégories
        $categories = $categoriesRepository->findAll();

        // Créer un tableau pour stocker les infos de wishlist
        $productsInWishlist = [];

        // Vérifier si l'utilisateur est connecté
        if ($this->getUser()) {
            // Récupérer tous les produits dans la wishlist de l'utilisateur
            $wishlistItems = $wishlistRepository->findBy(['user' => $this->getUser()]);

            // Créer un tableau associatif avec les IDs des produits dans la wishlist
            foreach ($wishlistItems as $item) {
                $productsInWishlist[$item->getProduct()->getId()] = true;
            }

            // Filtrer les produits pour n'afficher que ceux dans la wishlist si demandé
            if ($wishlistOnly) {
                $allProducts = array_filter($allProducts, function ($product) use ($productsInWishlist) {
                    return isset($productsInWishlist[$product->getId()]);
                });
            }
        } elseif ($wishlistOnly) {
            // Si l'utilisateur n'est pas connecté mais a demandé les favoris,
            // renvoyer une liste vide
            $allProducts = [];
        }

        return $this->render('app/catalogue.html.twig', [
            'controller_name' => 'Catalogue',
            'allProducts' => $allProducts,
            'productsInWishlist' => $productsInWishlist,
            'categories' => $categories,
            'wishlistOnly' => $wishlistOnly,
        ]);
    }

    // route pour la page carte cadeau
    #[Route('/carte-cadeau', name: 'app_carte_cadeau')]
    public function carteCadeau(): Response
    {
        return $this->render('app/carte_cadeau.html.twig', [
            'controller_name' => 'Carte cadeau',
        ]);
    }

    // route pour rediriger /produit vers le catalogue
    #[Route('/produit', name: 'app_produit_redirect')]
    public function produitRedirect(): Response
    {
        return $this->redirectToRoute('app_catalogue');
    }

    // route pour la page produit en détail avec l'id du produit et la possibilité de l'ajouter au panier
    #[Route('/produit/{id}', name: 'app_produit')]
    public function produit(
        int $id,
        ProductsRepository $repo,
        WishlistRepository $wishlistRepository
    ): Response {
        // Récupérer le produit par son ID
        $detailProduit = $repo->find($id);

        if (!$detailProduit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        // Vérifier si le produit est dans la wishlist de l'utilisateur
        $isInWishlist = false;
        if ($this->getUser()) {
            $wishlistItem = $wishlistRepository->findOneBy([
                'user' => $this->getUser(),
                'product' => $detailProduit
            ]);

            $isInWishlist = $wishlistItem !== null;
        }

        return $this->render('app/produit.html.twig', [
            'controller_name' => 'Produit',
            'detailProduit' => $detailProduit,
            'is_in_wishlist' => $isInWishlist
        ]);
    }


    // Modifiez cette méthode pour rediriger vers la route de login existante
    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    // // route pour la page contact
    // #[Route('/contact', name: 'app_contact')]
    // public function contact(): Response
    // {
    //     return $this->render('app/contact.html.twig', [
    //         'controller_name' => 'Contact',
    //     ]);
    // }

    // route pour la page mentions légales
    #[Route('/mentions', name: 'app_mentions')]
    public function mentions(): Response
    {
        return $this->render('app/mentions.html.twig', [
            'controller_name' => 'Mentions légales',
        ]);
    }

    // route pour la page politique de confidentialité
    #[Route('/pdc', name: 'app_pdc')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('app/pdc.html.twig', [
            'controller_name' => 'Politique de confidentialité',
        ]);
    }

    // route pour la page conditions générales de vente
    #[Route('/cgv', name: 'app_cgv')]
    public function conditionsGeneralesVente(): Response
    {
        return $this->render('app/cgv.html.twig', [
            'controller_name' => 'Conditions générales de vente',
        ]);
    }

    // route pour la page politique de remboursement
    #[Route('/premboursement', name: 'app_premboursement')]
    public function politiqueRemboursement(): Response
    {
        return $this->render('app/premboursement.html.twig', [
            'controller_name' => 'Politique de remboursement',
        ]);
    }
}
