<?php

namespace App\Security;

use App\Entity\Users;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class EmailVerificationAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    public function supports(Request $request): bool
    {
        // Ne rien faire ici, cette classe est juste un décorateur qui vérifie
        // si l'utilisateur a confirmé son email après l'authentification
        return false;
    }

    public function authenticate(Request $request): Passport
    {
        // Cette méthode ne sera pas appelée car supports() retourne false
        throw new \LogicException('This code should not be reached.');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Vérification si l'utilisateur a confirmé son email
        $user = $token->getUser();
        if ($user instanceof Users && !$user->isVerified()) {
            // L'utilisateur n'a pas confirmé son email
            throw new CustomUserMessageAuthenticationException(
                'Veuillez confirmer votre adresse email avant de vous connecter. 
                <a href="' . $this->urlGenerator->generate('app_verify_resend_email') . '">Renvoyer l\'email de vérification</a>'
            );
        }

        // Si l'utilisateur a confirmé son email, on le laisse se connecter normalement
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Par défaut rediriger vers la page d'accueil
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): ?Response
    {
        // Cette méthode ne sera pas appelée car supports() retourne false
        throw new \LogicException('This code should not be reached.');
    }
}
