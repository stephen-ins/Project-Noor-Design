<?php

namespace App\Controller;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    // route pour la page panier
    #[Route('/cart', name: 'app_panier')]
    public function index(SessionInterface $session, ProductsRepository $productsRepository): Response
    {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productsRepository->find($id);
            if ($product) {
                $cartWithData[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
                $total += $product->getPrix() * $quantity;
            }
        }

        return $this->render('cart/panier.html.twig', [
            'controller_name' => 'CartController',
            'items' => $cartWithData,
            'total' => $total
        ]);
    }

    // Ajouter un produit au panier
    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add($id, Request $request, SessionInterface $session, ProductsRepository $productsRepository): Response
    {
        // Vérifier si le produit existe
        $product = $productsRepository->find($id);
        if (!$product) {
            $this->addFlash('error', 'Ce produit n\'existe pas');
            return $this->redirectToRoute('app_catalogue');
        }

        // Vérifier le stock
        if ($product->getStock() <= 0) {
            $this->addFlash('error', 'Ce produit n\'est plus en stock');
            return $this->redirectToRoute('app_catalogue');
        }

        $cart = $session->get('cart', []);
        // Récupérer la quantité depuis le corps de la requête POST
        $quantity = (int)$request->request->get('quantity', 1);

        if (!empty($cart[$id])) {
            $cart[$id] += $quantity;
        } else {
            $cart[$id] = $quantity;
        }

        // Vérifier que la quantité ne dépasse pas le stock
        if ($cart[$id] > $product->getStock()) {
            $cart[$id] = $product->getStock();
            $this->addFlash('warning', 'La quantité a été ajustée au stock disponible');
        }

        $session->set('cart', $cart);
        $this->addFlash('success', 'Le produit a été ajouté au panier');

        return $this->redirectToRoute('app_panier');
    }

    // Supprimer un produit du panier
    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove($id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);
        return $this->redirectToRoute('app_panier');
    }

    // Mettre à jour la quantité d'un produit dans le panier
    #[Route('/cart/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function update($id, Request $request, SessionInterface $session, ProductsRepository $productsRepository): Response
    {
        $quantity = (int)$request->request->get('quantity', 1);
        $cart = $session->get('cart', []);

        if ($quantity > 0) {
            // Vérifier le stock
            $product = $productsRepository->find($id);
            if ($product && $quantity > $product->getStock()) {
                $quantity = $product->getStock();
                $this->addFlash('warning', 'La quantité a été ajustée au stock disponible');
            }

            $cart[$id] = $quantity;
        } else {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);
        return $this->redirectToRoute('app_panier');
    }
}
