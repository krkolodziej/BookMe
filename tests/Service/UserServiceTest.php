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
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasherMock = $this->createMock(UserPasswordHasherInterface::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->csrfTokenManagerMock = $this->createMock(CsrfTokenManagerInterface::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->sessionMock = $this->createMock(SessionInterface::class);
        
        $this->requestStackMock
            ->method('getSession')
            ->willReturn($this->sessionMock);
        
        $this->userService = new UserService(
            $this->entityManagerMock,
            $this->passwordHasherMock,
            $this->userRepositoryMock,
            $this->csrfTokenManagerMock,
            $this->tokenStorageMock,
            $this->requestStackMock
        );
    }

    
    public function testUpdateProfileWithPassword(): void
    {
        $user = $this->createMock(User::class);
        $plainPassword = 'new_password';
        $hashedPassword = 'hashed_password';
        
        $this->passwordHasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword)
            ->willReturn($hashedPassword);
            
        $user->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);
        
        $this->entityManagerMock
            ->expects($this->once())
            ->method('flush');
        
        $result = $this->userService->updateProfile($user, $plainPassword);
        
        $this->assertSame($user, $result);
    }
    

    public function testUpdateProfileWithoutPassword(): void
    {
        $user = $this->createMock(User::class);
        
        $this->passwordHasherMock
            ->expects($this->never())
            ->method('hashPassword');
            
        $user->expects($this->never())
            ->method('setPassword');
        
        $this->entityManagerMock
            ->expects($this->once())
            ->method('flush');
        
        $result = $this->userService->updateProfile($user, null);
        
        $this->assertSame($user, $result);
    }
    
   
    public function testDeleteAccountWithValidToken(): void
    {
        $user = $this->createMock(User::class);
        $csrfToken = 'valid_token';
        
        $this->csrfTokenManagerMock
            ->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(function (CsrfToken $token) use ($csrfToken) {
                return $token->getId() === 'delete-account' && $token->getValue() === $csrfToken;
            }))
            ->willReturn(true);
        
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('remove')
            ->with($user, true);
        
        $this->sessionMock
            ->expects($this->once())
            ->method('invalidate');
        
        $this->tokenStorageMock
            ->expects($this->once())
            ->method('setToken')
            ->with(null);
        
        $this->userService->deleteAccount($user, $csrfToken);
        
        $this->assertTrue(true);
    }
    
    
    public function testDeleteAccountWithInvalidToken(): void
    {
        $user = $this->createMock(User::class);
        $csrfToken = 'invalid_token';
        
        $this->csrfTokenManagerMock
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false);
        
        $this->expectException(BadRequestHttpException::class);
        
        $this->userRepositoryMock
            ->expects($this->never())
            ->method('remove');
        
        $this->userService->deleteAccount($user, $csrfToken);
    }
} 