<?php

namespace App\Repository;

use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Users>
 */
class UsersRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Users::class);
    }

    /**
     * Utilisé pour mettre à niveau (rehash) automatiquement le mot de passe de l'utilisateur 
     * lorsque l'encodage a changé
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Users) {
            throw new UnsupportedUserException(sprintf('Les instances de "%s" ne sont pas supportées.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Trouve tous les utilisateurs avec le rôle ROLE_ADMIN
     * @return Users[] Returns an array of Users objects with admin role
     */
    public function findAdmins(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :val) = 1')
            ->setParameter('val', '"ROLE_ADMIN"')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Trouve tous les utilisateurs inscrits après une certaine date
     * @return Users[] Returns an array of Users objects
     */
    public function findNewUsers(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.creation_date >= :val')
            ->setParameter('val', $since)
            ->orderBy('u.creation_date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recherche d'utilisateurs par nom, prénom ou email
     * @return Users[] Returns an array of Users objects
     */
    public function searchUsers(string $term): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.nom LIKE :term OR u.prenom LIKE :term OR u.email LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    //    /**
    //     * @return Users[] Returns an array of Users objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Users
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
