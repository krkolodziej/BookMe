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

        $this->userRepository
            ->expects($this->once())
            ->method('getAllUsers')
            ->with($searchTerm, $page, $pageSize, $sort, $direction)
            ->willReturn($expectedResult);

        $result = $this->adminUserService->getAllUsers($searchTerm, $page, $pageSize, $sort, $direction);

        $this->assertSame($expectedResult, $result);
    }

    public function testCreateUser()
    {
        $user = $this->adminUserService->createUser();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('customer', $user->getUserType());
    }

    public function testHashPassword()
    {
        $user = $this->createMock(User::class);
        $plainPassword = 'password123';
        $hashedPassword = 'hashed_password';

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword)
            ->willReturn($hashedPassword);

        $user->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);

        $this->adminUserService->hashPassword($user, $plainPassword);
    }

    public function testCreateEmployeeForUserSuccess()
    {
        $user = $this->createMock(User::class);
        $serviceId = 1;
        $service = $this->createMock(Service::class);

        $this->serviceRepository
            ->expects($this->once())
            ->method('find')
            ->with($serviceId)
            ->willReturn($service);

        $user->expects($this->once())
            ->method('setUserType')
            ->with('employee');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $employee = $this->adminUserService->createEmployeeForUser($user, $serviceId);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertSame($user, $employee->getUser());
        $this->assertSame($service, $employee->getService());
    }

    public function testCreateEmployeeForUserServiceNotFound()
    {
        $user = $this->createMock(User::class);
        $serviceId = 999;

        $this->serviceRepository
            ->expects($this->once())
            ->method('find')
            ->with($serviceId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Serwis nie został znaleziony.');

        $this->adminUserService->createEmployeeForUser($user, $serviceId);
    }

    public function testSaveUser()
    {
        $user = $this->createMock(User::class);
        $flush = true;

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user, $flush);

        $this->adminUserService->saveUser($user, $flush);
    }

    public function testSaveEmployee()
    {
        $employee = $this->createMock(Employee::class);
        $flush = true;

        $this->employeeRepository
            ->expects($this->once())
            ->method('save')
            ->with($employee, $flush);

        $this->adminUserService->saveEmployee($employee, $flush);
    }

    public function testGetUserByIdSuccess()
    {
        $userId = 1;
        $user = $this->createMock(User::class);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $result = $this->adminUserService->getUserById($userId);

        $this->assertSame($user, $result);
    }

    public function testGetUserByIdNotFound()
    {
        $userId = 999;

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono użytkownika o podanym identyfikatorze');

        $this->adminUserService->getUserById($userId);
    }

    public function testUpdateUser()
    {
        $user = $this->createMock(User::class);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->adminUserService->updateUser($user);
    }

    public function testDeleteUserSuccess()
    {
        $userId = 1;
        $csrfToken = 'valid_token';
        $user = $this->createMock(User::class);

        $user->method('getId')->willReturn($userId);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(function($token) use ($userId, $csrfToken) {
                return $token->getId() === 'delete'.$userId && $token->getValue() === $csrfToken;
            }))
            ->willReturn(true);

        $this->userRepository
            ->expects($this->once())
            ->method('remove')
            ->with($user, true);

        $this->adminUserService->deleteUser($userId, $csrfToken);
    }

    public function testDeleteUserInvalidToken()
    {
        $userId = 1;
        $csrfToken = 'invalid_token';
        $user = $this->createMock(User::class);

        $user->method('getId')->willReturn($userId);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Nieprawidłowy token CSRF');

        $this->adminUserService->deleteUser($userId, $csrfToken);
    }
} 