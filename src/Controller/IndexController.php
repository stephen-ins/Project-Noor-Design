<?php

namespace App\Controller;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    public function catalogue(ProductsRepository $repoAllProducts): Response
    {
        // Récupérer tous les produits de la base de données
        $allProducts = $repoAllProducts->findAll();

        return $this->render('app/catalogue.html.twig', [
            'controller_name' => 'Catalogue',
            'allProducts' => $allProducts,
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
    public function produit(int $id, ProductsRepository $repo): Response
    {
        // Récupérer le produit par son ID
        $detailProduit = $repo->find($id);

        return $this->render('app/produit.html.twig', [
            'controller_name' => 'Produit',
            'detailProduit' => $detailProduit,
        ]);
    }

    // route pour la page connexion
    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(): Response
    {
        return $this->render('app/connexion.html.twig', [
            'controller_name' => 'Connexion',
        ]);
    }

    // route pour la page contact
    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('app/contact.html.twig', [
            'controller_name' => 'Contact',
        ]);
    }

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
