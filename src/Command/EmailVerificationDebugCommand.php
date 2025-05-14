<?php

namespace App\Command;

use App\Entity\Users;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

#[AsCommand(
    name: 'app:verify-email:debug',
    description: 'Outil de diagnostic pour le système de vérification d\'email',
)]
class EmailVerificationDebugCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailVerifier $emailVerifier
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de l\'utilisateur à diagnostiquer')
            ->addArgument('action', InputArgument::OPTIONAL, 'Action à effectuer: status, resend, force-verify', 'status')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $action = $input->getArgument('action');

        $io->title('Diagnostic du système de vérification d\'email');

        // Trouver l'utilisateur
        $user = $this->entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('Aucun utilisateur trouvé avec l\'email "%s"', $email));
            return Command::FAILURE;
        }

        $io->section('Informations utilisateur');
        $io->table(
            ['ID', 'Nom', 'Prénom', 'Email', 'Vérifié'],
            [
                [
                    $user->getId(),
                    $user->getNom(),
                    $user->getPrenom(),
                    $user->getEmail(),
                    $user->isVerified() ? 'Oui' : 'Non'
                ]
            ]
        );

        switch ($action) {
            case 'status':
                // Afficher uniquement l'état actuel
                if ($user->isVerified()) {
                    $io->success('L\'utilisateur a déjà vérifié son email.');
                } else {
                    $io->warning('L\'utilisateur n\'a pas encore vérifié son email.');
                }
                break;

            case 'resend':
                // Renvoyer l'email de vérification
                try {
                    $io->info('Envoi d\'un nouvel email de vérification...');

                    $this->emailVerifier->sendEmailConfirmation(
                        'app_verify_email',
                        $user,
                        (new TemplatedEmail())
                            ->from(new Address('contact@noordesign.stephen-ins.com', 'Noor Design'))
                            ->to($user->getEmail())
                            ->subject('Confirmez votre adresse email - Noor Design')
                            ->htmlTemplate('registration/confirmation_email.html.twig')
                            ->context([
                                'name' => $user->getPrenom() . ' ' . $user->getNom()
                            ])
                    );

                    $io->success('Email de vérification envoyé avec succès!');
                } catch (\Exception $e) {
                    $io->error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
                    return Command::FAILURE;
                }
                break;

            case 'force-verify':
                // Forcer la vérification de l'email
                $user->setIsVerified(true);
                $this->entityManager->flush();
                $io->success('L\'email de l\'utilisateur a été marqué comme vérifié avec succès!');
                break;

            default:
                $io->error('Action inconnue: ' . $action);
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
