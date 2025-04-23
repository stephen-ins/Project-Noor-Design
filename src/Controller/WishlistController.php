<?php

namespace App\Controller;

use App\Entity\Products;
use App\Entity\Wishlist;
use App\Repository\WishlistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WishlistController extends AbstractController
{
    // Ajouter un produit à la wishlist
    #[Route('/wishlist/add/{id}', name: 'wishlist_add')]
    #[IsGranted('ROLE_USER')]
    public function add(
        Products $product,
        WishlistRepository $wishlistRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $user = $this->getUser();

        // Vérifier si le produit est déjà dans la wishlist
        $existingItem = $wishlistRepository->findOneBy([
            'user' => $user,
            'product' => $product
        ]);

        if (!$existingItem) {
            // Créer un nouvel élément de wishlist
            $wishlistItem = new Wishlist();
            $wishlistItem->setUser($user);
            $wishlistItem->setProduct($product);
            $wishlistItem->setCreatedAt(new \DateTimeImmutable()); // Initialiser createdAt

            $entityManager->persist($wishlistItem);
            $entityManager->flush();

            $message = 'Produit ajouté à votre liste de favoris';
            $success = true;
        } else {
            $message = 'Ce produit est déjà dans votre liste de favoris';
            $success = false;
        }

        // Si c'est une requête AJAX, retourner une réponse JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => $success,
                'message' => $message
            ]);
        }

        // Sinon, ajouter un message flash et rediriger
        $this->addFlash($success ? 'success' : 'info', $message);
        
        // Rediriger vers la page précédente
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_catalogue'));
    }

    // Supprimer un produit de la wishlist
    #[Route('/wishlist/remove/{id}', name: 'wishlist_remove')]
    #[IsGranted('ROLE_USER')]
    public function remove(
        Products $product,
        WishlistRepository $wishlistRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $user = $this->getUser();

        // Trouver l'élément dans la wishlist
        $wishlistItem = $wishlistRepository->findOneBy([
            'user' => $user,
            'product' => $product
        ]);

        if ($wishlistItem) {
            $entityManager->remove($wishlistItem);
            $entityManager->flush();
            $message = 'Produit retiré de votre liste de favoris';
            $success = true;
        } else {
            $message = 'Ce produit n\'est pas dans votre liste de favoris';
            $success = false;
        }

        // Si c'est une requête AJAX, retourner une réponse JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => $success,
                'message' => $message
            ]);
        }

        // Sinon, ajouter un message flash et rediriger
        $this->addFlash($success ? 'success' : 'info', $message);
        
        // Rediriger vers la page précédente
        $referer = $request->headers->get('referer');
        if (str_contains($referer ?: '', 'wishlist')) {
            return $this->redirectToRoute('app_account');
        }
        return $this->redirect($referer ?: $this->generateUrl('app_account'));
    }
}
