<?php

namespace App\Repository;

use App\Entity\Employee;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function findByServiceEncodedName(string $serviceEncodedName): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.service', 's')
            ->leftJoin('e.user', 'u')
            ->addSelect('s', 'u')
            ->andWhere('s.encodedName = :encodedName')
            ->setParameter('encodedName', $serviceEncodedName)
            ->getQuery()
            ->getResult();
    }

    public function findOneByUser(User $user): ?Employee
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Employee $employee, bool $flush = false): void
    {
        $this->getEntityManager()->persist($employee);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Employee $employee, bool $flush = false): void
    {
        $this->getEntityManager()->remove($employee);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getServiceEmployees(
        Service $service,
        int $page = 1,
        int $pageSize = 10,
        string $sort = 'lastName',
        string $direction = 'asc'
    ): array {
        try {
            $totalQb = $this->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->where('e.service = :service')
                ->setParameter('service', $service);

            $total = $totalQb->getQuery()->getSingleScalarResult();

            $qb = $this->createQueryBuilder('e')
                ->where('e.service = :service')
                ->setParameter('service', $service)
                ->leftJoin('e.user', 'u')
                ->addSelect('u');

            $validUserSortFields = ['firstName', 'lastName', 'email'];

            if (in_array($sort, $validUserSortFields)) {
                $sortDirection = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
                $qb->orderBy('u.' . $sort, $sortDirection);
            } else {
                $qb->orderBy('u.lastName', 'ASC');
            }

            $qb->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);

            $employees = $qb->getQuery()->getResult();

            $mappedEmployees = array_map(function($employee) {
                $user = $employee->getUser();
                return [
                    'id' => $employee->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                    'gender' => $user->getGender(),
                    'avatarUrl' => $user->getAvatarUrl(),
                    'bookingsCount' => $employee->getBookings()->count()
                ];
            }, $employees);

            return [
                'items' => $mappedEmployees,
                'total' => $total,
                'totalPages' => ceil($total / $pageSize)
            ];

        } catch (\Exception $e) {
            throw new \RuntimeException('Error fetching employees: ' . $e->getMessage());
        }
    }

    public function isUserAlreadyEmployee(User $user): bool
    {
        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function getAllEmployees(string $searchTerm = '', int $page = 1, int $pageSize = 10, string $sort = 'lastName', string $direction = 'asc'): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.user', 'u')
            ->leftJoin('e.service', 's');

        if (!empty($searchTerm)) {
            $qb->andWhere('s.name LIKE :term OR u.firstName LIKE :term OR u.lastName LIKE :term OR CONCAT(u.firstName, \' \', u.lastName) LIKE :term')
                ->setParameter('term', '%' . $searchTerm . '%');
        }

        $sortField = 'u.' . $sort;
        if ($sort === 'serviceName') {
            $sortField = 's.name';
        }
        $qb->orderBy($sortField, $direction);

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();

        $qb->setMaxResults($pageSize)
            ->setFirstResult(($page - 1) * $pageSize)
            ->select('e', 'u', 's');

        $items = $qb->getQuery()->getResult();

        $employees = [];
        foreach ($items as $employee) {
            $user = $employee->getUser();
            $service = $employee->getService();

            $employees[] = [
                'id' => $employee->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'gender' => $user->getGender(),
                'avatarUrl' => $user->getAvatarUrl(),
                'serviceName' => $service ? $service->getName() : 'N/A',
                'serviceId' => $service ? $service->getId() : null,
                'serviceEncodedName' => $service ? $service->getEncodedName() : null
            ];
        }

        return [
            'items' => $employees,
            'total' => $total,
            'totalPages' => ceil($total / $pageSize)
        ];
    }

    public function getEmployeeStatistics(int $employeeId): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $result = $qb
            ->select('e.id')
            ->addSelect('COUNT(o.id) as opinionsCount')
            ->addSelect('COALESCE(AVG(o.rating), 0) as averageRating')
            ->from('App\Entity\Employee', 'e')
            ->leftJoin('App\Entity\Booking', 'b', 'WITH', 'b.employee = e.id')
            ->leftJoin('App\Entity\Opinion', 'o', 'WITH', 'o.booking = b.id')
            ->where('e.id = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->groupBy('e.id')
            ->getQuery()
            ->getOneOrNullResult();

        if (!$result) {
            return [
                'opinionsCount' => 0,
                'averageRating' => 0,
            ];
        }

        return [
            'opinionsCount' => (int)$result['opinionsCount'],
            'averageRating' => round((float)$result['averageRating'], 1)
        ];
    }

    /**
     * Get total number of employees
     */
    public function getTotalCount(): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get number of active employees
     */
    public function getActiveCount(): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.user', 'u')
            ->getQuery()
            ->getSingleScalarResult();
    }
}