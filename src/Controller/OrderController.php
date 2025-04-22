<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\OrderDetails;
use App\Enum\OrderStatus;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OrderController extends AbstractController
{
    // Création d'une commande à partir du panier
    #[Route('/order/create', name: 'create_order')]
    #[IsGranted('ROLE_USER')]
    public function createOrder(
        SessionInterface $session,
        ProductsRepository $productsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer le panier
        $cart = $session->get('cart', []);

        // Vérifier si le panier n'est pas vide
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('app_panier');
        }

        // Calculer le total
        $total = 0;
        foreach ($cart as $id => $quantity) {
            $product = $productsRepository->find($id);
            if ($product) {
                $total += $product->getPrix() * $quantity;
            }
        }

        // Ajouter les frais de livraison si le total est inférieur à 100€
        $shippingFee = 0;
        if ($total < 100) {
            $shippingFee = 5.90;
        }

        $totalWithShipping = $total + $shippingFee;

        // Créer la commande
        $order = new Orders();
        $order->setUser($this->getUser());
        $order->setDateCommande(new \DateTimeImmutable());
        $order->setTotal($totalWithShipping); // Total avec frais de port
        $order->setStatus(OrderStatus::EN_ATTENTE);

        // Ajouter une information sur les frais de livraison
        if (method_exists($order, 'setShippingFee')) {
            $order->setShippingFee($shippingFee);
        }

        $stockError = false;

        // Ajouter les produits à la commande
        foreach ($cart as $productId => $quantity) {
            $product = $productsRepository->find($productId);

            if (!$product) {
                continue; // Ignorer les produits inexistants
            }

            // Vérifier le stock
            if ($product->getStock() < $quantity) {
                $this->addFlash('error', "Stock insuffisant pour {$product->getNom()} (seulement {$product->getStock()} disponibles)");
                $stockError = true;
                continue;
            }

            // Créer le détail de commande
            $orderDetail = new OrderDetails();
            $orderDetail->setProduct($product);
            $orderDetail->setQuantity($quantity);
            $orderDetail->setPrix($product->getPrix());
            $orderDetail->setOrder($order);

            // Mettre à jour le stock du produit
            $product->setStock($product->getStock() - $quantity);

            // Ajouter le détail à la commande
            $order->addOrderDetail($orderDetail);

            $entityManager->persist($orderDetail);
            $entityManager->persist($product);
        }

        // Si erreur de stock, rediriger vers le panier
        if ($stockError) {
            return $this->redirectToRoute('app_panier');
        }

        // Persister la commande
        $entityManager->persist($order);

        try {
            $entityManager->flush();

            // Vider le panier
            $session->remove('cart');

            $this->addFlash('success', 'Votre commande a été enregistrée avec succès');

            // Rediriger vers une page de confirmation
            return $this->redirectToRoute('order_confirmation', ['id' => $order->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création de votre commande');
            return $this->redirectToRoute('app_panier');
        }
    }

    // Page de confirmation de la commande
    #[Route('/order/confirmation/{id}', name: 'order_confirmation')]
    #[IsGranted('ROLE_USER')]
    public function orderConfirmation(Orders $order): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire de la commande
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette commande');
        }

        // Générer le numéro de commande avec le format défini dans services.yaml
        $orderNumberFormat = $this->getParameter('order_number_format');
        $orderDate = $order->getDateCommande()->format('Ymd');
        $orderNumber = sprintf($orderNumberFormat, $orderDate, $order->getId());

        return $this->render('order/confirmation.html.twig', [
            'order' => $order,
            'order_number' => $orderNumber
        ]);
    }

    // Historique des commandes de l'user
    #[Route('/order/history', name: 'order_history')]
    #[IsGranted('ROLE_USER')]
    public function orderHistory(): Response
    {
        $user = $this->getUser();

        // Récupérer les commandes de l'utilisateur triées par date décroissante
        $orders = $user->getOrders()->toArray();

        // Trier les commandes par date (de la plus récente à la plus ancienne)
        usort($orders, function ($a, $b) {
            return $b->getDateCommande() <=> $a->getDateCommande();
        });

        return $this->render('order/history.html.twig', [
            'orders' => $orders
        ]);
    }
}
