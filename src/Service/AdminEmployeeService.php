<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminEmployeeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmployeeRepository $employeeRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    /**
     *
     * @param string $encodedName
     * @return Service
     * @throws NotFoundHttpException
     */
    public function getServiceByEncodedName(string $encodedName): Service
    {
        $service = $this->serviceRepository->findOneBy(['encodedName' => $encodedName]);

        if (!$service) {
            throw new NotFoundHttpException('Nie znaleziono serwisu o podanym identyfikatorze');
        }

        return $service;
    }

    /**
     *
     * @param Service $service
     * @param int $page
     * @param int $pageSize
     * @param string $sort
     * @param string $direction
     * @return array
     */
    public function getServiceEmployees(
        Service $service,
        int $page = 1,
        int $pageSize = 10,
        string $sort = 'lastName',
        string $direction = 'asc'
    ): array {
        return $this->employeeRepository->getServiceEmployees($service, $page, $pageSize, $sort, $direction);
    }

    /**
     *
     * @param string $searchTerm
     * @param int $page
     * @param int $pageSize
     * @param string $sort
     * @param string $direction
     * @return array
     */
    public function getAllEmployees(
        string $searchTerm = '',
        int $page = 1,
        int $pageSize = 10,
        string $sort = 'lastName',
        string $direction = 'asc'
    ): array {
        return $this->employeeRepository->getAllEmployees($searchTerm, $page, $pageSize, $sort, $direction);
    }

    /**
     *
     * @param Service $service
     * @return array
     */
    public function createNewEmployee(Service $service): array
    {
        $user = new User();
        $user->setUserType('employee');

        $employee = new Employee();
        $employee->setUser($user);
        $employee->setService($service);

        return [
            'user' => $user,
            'employee' => $employee
        ];
    }

    /**
     *
     * @return array
     */
    public function createNewGeneralEmployee(): array
    {
        $user = new User();
        $user->setUserType('employee');

        $employee = new Employee();
        $employee->setUser($user);

        return [
            'user' => $user,
            'employee' => $employee
        ];
    }

    /**
     *
     * @param User $user
     * @param Employee $employee
     * @param Service $service
     * @param string $plainPassword
     * @return Employee
     */
    public function saveNewEmployee(User $user, Employee $employee, Service $service, string $plainPassword): Employee
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user, true);

        $employee->setUser($user);
        $employee->setService($service);
        $this->employeeRepository->save($employee, true);

        return $employee;
    }

    /**
     *
     * @param User $user
     * @param Employee $employee
     * @param string $plainPassword
     * @return Employee
     * @throws BadRequestHttpException
     */
    public function checkAndSaveNewEmployee(User $user, Employee $employee, string $plainPassword): Employee
    {
        if (!$employee->getService()) {
            throw new BadRequestHttpException('Proszę wybrać serwis dla pracownika');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user, true);

        $employee->setUser($user);
        $this->employeeRepository->save($employee, true);

        return $employee;
    }

    /**
     *
     * @param int $id
     * @param Service $service
     * @return Employee
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function getEmployeeForEdit(int $id, Service $service): Employee
    {
        $employee = $this->employeeRepository->find($id);

        if (!$employee) {
            throw new NotFoundHttpException('Nie znaleziono pracownika o podanym identyfikatorze');
        }

        if ($employee->getService()->getId() !== $service->getId()) {
            throw new AccessDeniedException('Ten pracownik nie należy do wybranego serwisu');
        }

        return $employee;
    }

    /**
     *
     * @param int $id
     * @return Employee
     * @throws NotFoundHttpException
     */
    public function getEmployeeById(int $id): Employee
    {
        $employee = $this->employeeRepository->find($id);

        if (!$employee) {
            throw new NotFoundHttpException('Nie znaleziono pracownika o podanym identyfikatorze');
        }

        return $employee;
    }

    /**
     *
     * @param User $user
     * @param string|null $plainPassword
     * @return void
     */
    public function updateEmployee(User $user, ?string $plainPassword = null): void
    {
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->flush();
    }

    /**
     *
     * @param int $id
     * @param Service $service
     * @param string $csrfToken
     * @return void
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function deleteEmployee(int $id, Service $service, string $csrfToken): void
    {
        $employee = $this->getEmployeeForEdit($id, $service);

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete'.$employee->getId(), $csrfToken))) {
            throw new AccessDeniedException('Nieprawidłowy token CSRF');
        }

        $this->employeeRepository->remove($employee, true);
    }

    /**
     *
     * @param int $id
     * @param string $csrfToken
     * @return void
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function deleteGeneralEmployee(int $id, string $csrfToken): void
    {
        $employee = $this->getEmployeeById($id);

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete'.$employee->getId(), $csrfToken))) {
            throw new AccessDeniedException('Nieprawidłowy token CSRF');
        }

        $this->employeeRepository->remove($employee, true);
    }
} 