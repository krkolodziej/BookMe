<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\User;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminUserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    /**
     * @param string $searchTerm
     * @param int $page
     * @param int $pageSize
     * @param string $sort
     * @param string $direction
     * @return array
     */
    public function getAllUsers(
        string $searchTerm = '',
        int $page = 1,
        int $pageSize = 10,
        string $sort = 'lastName',
        string $direction = 'asc'
    ): array {
        return $this->userRepository->getAllUsers($searchTerm, $page, $pageSize, $sort, $direction);
    }

    /**
     * @return User
     */
    public function createUser(): User
    {
        $user = new User();
        $user->setUserType('customer');
        
        return $user;
    }

    /**
     * @param User $user
     * @param string $plainPassword
     * @return void
     */
    public function hashPassword(User $user, string $plainPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
    }

    /**
     * @param User $user
     * @param int $serviceId
     * @return Employee
     */
    public function createEmployeeForUser(User $user, int $serviceId): Employee
    {
        $service = $this->serviceRepository->find($serviceId);
        
        if (!$service) {
            throw new NotFoundHttpException('Serwis nie został znaleziony.');
        }
        
        $user->setUserType('employee');
        $this->entityManager->flush();
        
        $employee = new Employee();
        $employee->setUser($user);
        $employee->setService($service);
        
        return $employee;
    }

    /**
     * @param User $user
     * @param bool $flush
     * @return void
     */
    public function saveUser(User $user, bool $flush = true): void
    {
        $this->userRepository->save($user, $flush);
    }

    /**
     * @param Employee $employee
     * @param bool $flush
     * @return void
     */
    public function saveEmployee(Employee $employee, bool $flush = true): void
    {
        $this->employeeRepository->save($employee, $flush);
    }

    /**
     * @param int $id
     * @return User
     * @throws NotFoundHttpException
     */
    public function getUserById(int $id): User
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new NotFoundHttpException('Nie znaleziono użytkownika o podanym identyfikatorze');
        }

        return $user;
    }

    /**
     * @param User $user
     * @return void
     */
    public function updateUser(User $user): void
    {
        $this->entityManager->flush();
    }

    /**
     * @param int $id
     * @param string $csrfToken
     * @return void
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function deleteUser(int $id, string $csrfToken): void
    {
        $user = $this->getUserById($id);

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete'.$user->getId(), $csrfToken))) {
            throw new AccessDeniedException('Nieprawidłowy token CSRF');
        }

        $this->userRepository->remove($user, true);
    }
} 