<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UserServiceTest extends TestCase
{
    private $entityManagerMock;
    private $passwordHasherMock;
    private $userRepositoryMock;
    private $csrfTokenManagerMock;
    private $tokenStorageMock;
    private $requestStackMock;
    private $sessionMock;
    private $userService;

    protected function setUp(): void
    {
        // Tworzenie mocków dla zależności
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasherMock = $this->createMock(UserPasswordHasherInterface::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->csrfTokenManagerMock = $this->createMock(CsrfTokenManagerInterface::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->sessionMock = $this->createMock(SessionInterface::class);
        
        // Konfiguracja mocka requestStack aby zwracać sesję
        $this->requestStackMock
            ->method('getSession')
            ->willReturn($this->sessionMock);
        
        // Inicjalizacja serwisu z mockami
        $this->userService = new UserService(
            $this->entityManagerMock,
            $this->passwordHasherMock,
            $this->userRepositoryMock,
            $this->csrfTokenManagerMock,
            $this->tokenStorageMock,
            $this->requestStackMock
        );
    }

    /**
     * Test metody updateProfile z podanym hasłem
     */
    public function testUpdateProfileWithPassword(): void
    {
        // Przygotowanie
        $user = $this->createMock(User::class);
        $plainPassword = 'new_password';
        $hashedPassword = 'hashed_password';
        
        // Konfiguracja mocka hasher
        $this->passwordHasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword)
            ->willReturn($hashedPassword);
            
        // Konfiguracja mocka użytkownika
        $user->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);
        
        // Konfiguracja mocka entity managera
        $this->entityManagerMock
            ->expects($this->once())
            ->method('flush');
        
        // Wykonanie
        $result = $this->userService->updateProfile($user, $plainPassword);
        
        // Weryfikacja
        $this->assertSame($user, $result);
    }
    
    /**
     * Test metody updateProfile bez hasła
     */
    public function testUpdateProfileWithoutPassword(): void
    {
        // Przygotowanie
        $user = $this->createMock(User::class);
        
        // Konfiguracja mocka hasher - nie powinien być wywołany
        $this->passwordHasherMock
            ->expects($this->never())
            ->method('hashPassword');
            
        // Konfiguracja mocka użytkownika - nie powinien być wywołany setPassword
        $user->expects($this->never())
            ->method('setPassword');
        
        // Konfiguracja mocka entity managera
        $this->entityManagerMock
            ->expects($this->once())
            ->method('flush');
        
        // Wykonanie
        $result = $this->userService->updateProfile($user, null);
        
        // Weryfikacja
        $this->assertSame($user, $result);
    }
    
    /**
     * Test metody deleteAccount z prawidłowym tokenem
     */
    public function testDeleteAccountWithValidToken(): void
    {
        // Przygotowanie
        $user = $this->createMock(User::class);
        $csrfToken = 'valid_token';
        
        // Konfiguracja mocka csrf token manager
        $this->csrfTokenManagerMock
            ->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(function (CsrfToken $token) use ($csrfToken) {
                return $token->getId() === 'delete-account' && $token->getValue() === $csrfToken;
            }))
            ->willReturn(true);
        
        // Konfiguracja mocka user repository
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('remove')
            ->with($user, true);
        
        // Konfiguracja mocka session
        $this->sessionMock
            ->expects($this->once())
            ->method('invalidate');
        
        // Konfiguracja mocka token storage
        $this->tokenStorageMock
            ->expects($this->once())
            ->method('setToken')
            ->with(null);
        
        // Wykonanie
        $this->userService->deleteAccount($user, $csrfToken);
        
        // Weryfikacja - jeśli doszliśmy do tego miejsca, to test jest udany
        $this->assertTrue(true);
    }
    
    /**
     * Test metody deleteAccount z nieprawidłowym tokenem
     */
    public function testDeleteAccountWithInvalidToken(): void
    {
        // Przygotowanie
        $user = $this->createMock(User::class);
        $csrfToken = 'invalid_token';
        
        // Konfiguracja mocka csrf token manager
        $this->csrfTokenManagerMock
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false);
        
        // Oczekiwanie wyjątku
        $this->expectException(BadRequestHttpException::class);
        
        // Konfiguracja mocka user repository - nie powinien być wywołany
        $this->userRepositoryMock
            ->expects($this->never())
            ->method('remove');
        
        // Wykonanie
        $this->userService->deleteAccount($user, $csrfToken);
    }
} 