<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/bootstrap.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\SecurityBundle\Security;

// Charger l'application Symfony
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'entity manager
$entityManager = $container->get('doctrine.orm.entity_manager');

// Email à vérifier (à remplacer par l'email que vous avez utilisé lors de l'inscription)
$email = $argv[1] ?? null;

if (!$email) {
    echo "Utilisation : php bin/verify-user.php email@exemple.com\n";
    exit(1);
}

// Trouver l'utilisateur par email
$userRepository = $entityManager->getRepository(\App\Entity\Users::class);
$user = $userRepository->findOneBy(['email' => $email]);

if (!$user) {
    echo "Aucun utilisateur trouvé avec l'email : {$email}\n";
    exit(1);
}

// Marquer l'utilisateur comme vérifié
$user->setIsVerified(true);
$entityManager->persist($user);
$entityManager->flush();

echo "L'utilisateur {$email} a été marqué comme vérifié avec succès.\n";
echo "Vous pouvez maintenant vous connecter avec ce compte.\n";
