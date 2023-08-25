<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Question;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Drenso\OidcBundle\Model\OidcUserData;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Drenso\OidcBundle\Security\UserProvider\OidcUserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserProvider implements UserProviderInterface, OidcUserProviderInterface {

    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface {
        throw new \Exception('TODO: fill in loadUserByIdentifier() inside '.__FILE__);
    }

    public function refreshUser(UserInterface $user): UserInterface {
        return $this->userRepository->find($user->getId());
    }

    public function supportsClass(string $class): bool {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void {
    }

    public function ensureUserExists(string $userIdentifier, OidcUserData $userData) {
        $this->logger->info("ensureUserExists " . $userIdentifier);
        $user = $this->userRepository->findOneBySub($userIdentifier);
        if($user == null) {
            $user = new User($userIdentifier);
            $user->setName($userData->getUserDataString('username'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        return true;
    }

    public function loadOidcUser(string $userIdentifier): UserInterface {
        $this->logger->info("loadOidcUser " . $userIdentifier);
        return $this->userRepository->findOneBySub($userIdentifier);
    }
}