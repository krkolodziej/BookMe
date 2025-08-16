<?php

namespace App\Tests\Service;

use App\Entity\Employee;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Service\AdminUserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminUserServiceTest extends TestCase
{
    private $entityManager;
    private $userRepository;
    private $serviceRepository;
    private $employeeRepository;
    private $passwordHasher;
    private $csrfTokenManager;
    private $adminUserService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->serviceRepository = $this->createMock(ServiceRepository::class);
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->adminUserService = new AdminUserService(
            $this->entityManager,
            $this->userRepository,
            $this->serviceRepository,
            $this->employeeRepository,
            $this->passwordHasher,
            $this->csrfTokenManager
        );
    }

    public function testGetAllUsers()
    {
        // Przygotowanie danych
        $searchTerm = 'Jan';
        $page = 1;
        $pageSize = 10;
        $sort = 'lastName';
        $direction = 'asc';
        
        $expectedResult = [
            'items' => [
                $this->createMock(User::class),
                $this->createMock(User::class)
            ],
            'total' => 2,
            'totalPages' => 1
        ];

        // Konfiguracja mocka repozytorium użytkownika
        $this->userRepository
            ->expects($this->once())
            ->method('getAllUsers')
            ->with($searchTerm, $page, $pageSize, $sort, $direction)
            ->willReturn($expectedResult);

        // Wywołanie metody serwisu
        $result = $this->adminUserService->getAllUsers($searchTerm, $page, $pageSize, $sort, $direction);

        // Weryfikacja wyników
        $this->assertSame($expectedResult, $result);
    }

    public function testCreateUser()
    {
        // Wywołanie metody serwisu
        $user = $this->adminUserService->createUser();

        // Weryfikacja wyników
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('customer', $user->getUserType());
    }

    public function testHashPassword()
    {
        // Przygotowanie danych
        $user = $this->createMock(User::class);
        $plainPassword = 'password123';
        $hashedPassword = 'hashed_password';

        // Konfiguracja mocka passwordHasher
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword)
            ->willReturn($hashedPassword);

        // Konfiguracja mocka user
        $user->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);

        // Wywołanie metody serwisu
        $this->adminUserService->hashPassword($user, $plainPassword);
    }

    public function testCreateEmployeeForUserSuccess()
    {
        // Przygotowanie danych
        $user = $this->createMock(User::class);
        $serviceId = 1;
        $service = $this->createMock(Service::class);

        // Konfiguracja mocka service repository
        $this->serviceRepository
            ->expects($this->once())
            ->method('find')
            ->with($serviceId)
            ->willReturn($service);

        // Konfiguracja mocka user
        $user->expects($this->once())
            ->method('setUserType')
            ->with('employee');

        // Konfiguracja mocka entity manager
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Wywołanie metody serwisu
        $employee = $this->adminUserService->createEmployeeForUser($user, $serviceId);

        // Weryfikacja wyników
        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertSame($user, $employee->getUser());
        $this->assertSame($service, $employee->getService());
    }

    public function testCreateEmployeeForUserServiceNotFound()
    {
        // Przygotowanie danych
        $user = $this->createMock(User::class);
        $serviceId = 999;

        // Konfiguracja mocka service repository
        $this->serviceRepository
            ->expects($this->once())
            ->method('find')
            ->with($serviceId)
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Serwis nie został znaleziony.');

        // Wywołanie metody serwisu
        $this->adminUserService->createEmployeeForUser($user, $serviceId);
    }

    public function testSaveUser()
    {
        // Przygotowanie danych
        $user = $this->createMock(User::class);
        $flush = true;

        // Konfiguracja mocka repozytorium użytkownika
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user, $flush);

        // Wywołanie metody serwisu
        $this->adminUserService->saveUser($user, $flush);
    }

    public function testSaveEmployee()
    {
        // Przygotowanie danych
        $employee = $this->createMock(Employee::class);
        $flush = true;

        // Konfiguracja mocka repozytorium pracownika
        $this->employeeRepository
            ->expects($this->once())
            ->method('save')
            ->with($employee, $flush);

        // Wywołanie metody serwisu
        $this->adminUserService->saveEmployee($employee, $flush);
    }

    public function testGetUserByIdSuccess()
    {
        // Przygotowanie danych
        $userId = 1;
        $user = $this->createMock(User::class);

        // Konfiguracja mocka repozytorium użytkownika
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        // Wywołanie metody serwisu
        $result = $this->adminUserService->getUserById($userId);

        // Weryfikacja wyników
        $this->assertSame($user, $result);
    }

    public function testGetUserByIdNotFound()
    {
        // Przygotowanie danych
        $userId = 999;

        // Konfiguracja mocka repozytorium użytkownika
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono użytkownika o podanym identyfikatorze');

        // Wywołanie metody serwisu
        $this->adminUserService->getUserById($userId);
    }

    public function testUpdateUser()
    {
        // Przygotowanie danych
        $user = $this->createMock(User::class);

        // Konfiguracja mocka entity manager
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Wywołanie metody serwisu
        $this->adminUserService->updateUser($user);
    }

    public function testDeleteUserSuccess()
    {
        // Przygotowanie danych
        $userId = 1;
        $csrfToken = 'valid_token';
        $user = $this->createMock(User::class);

        // Konfiguracja mocka user
        $user->method('getId')->willReturn($userId);

        // Konfiguracja mocka repozytorium użytkownika
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        // Konfiguracja mocka csrf token manager
        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(function($token) use ($userId, $csrfToken) {
                return $token->getId() === 'delete'.$userId && $token->getValue() === $csrfToken;
            }))
            ->willReturn(true);

        // Konfiguracja mocka repozytorium użytkownika dla usunięcia
        $this->userRepository
            ->expects($this->once())
            ->method('remove')
            ->with($user, true);

        // Wywołanie metody serwisu
        $this->adminUserService->deleteUser($userId, $csrfToken);
    }

    public function testDeleteUserInvalidToken()
    {
        // Przygotowanie danych
        $userId = 1;
        $csrfToken = 'invalid_token';
        $user = $this->createMock(User::class);

        // Konfiguracja mocka user
        $user->method('getId')->willReturn($userId);

        // Konfiguracja mocka repozytorium użytkownika
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        // Konfiguracja mocka csrf token manager
        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false);

        // Oczekiwanie na wyjątek
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Nieprawidłowy token CSRF');

        // Wywołanie metody serwisu
        $this->adminUserService->deleteUser($userId, $csrfToken);
    }
} 