<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack
    ) {
    }

    /** 
     * @param User $user
     * @param string|null $plainPassword
     * @return User
     * @throws AccessDeniedException
     */
    public function updateProfile(User $user, ?string $plainPassword = null): User
    {
        if (!$user) {
            throw new AccessDeniedException('Musisz być zalogowany, aby edytować swój profil.');
        }

        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->flush();

        return $user;
    }

    /** 
     * @param User $user
     * @param string $csrfToken
     * @return void
     * @throws AccessDeniedException
     * @throws BadRequestHttpException
     */
    public function deleteAccount(User $user, string $csrfToken): void
    {
        if (!$user) {
            throw new AccessDeniedException('Musisz być zalogowany, aby usunąć swoje konto.');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete-account', $csrfToken))) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        $this->userRepository->remove($user, true);

        $this->invalidateSession();
    }

    /** 
     * @return void
     */
    private function invalidateSession(): void
    {
        $this->requestStack->getSession()->invalidate();
        $this->tokenStorage->setToken(null);
    }
} 