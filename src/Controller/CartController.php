<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{

    // route pour la page panier
    #[Route('/cart', name: 'app_panier')]
    public function index(): Response
    {
        return $this->render('cart/panier.html.twig', [
            'controller_name' => 'CartController',
        ]);
    }
}
