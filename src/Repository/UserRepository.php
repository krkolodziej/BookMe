<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
    public function save(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->remove($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function findAvailableEmployees(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.userType = :userType')
            ->setParameter('userType', 'employee')
            ->leftJoin('App\Entity\Employee', 'e', 'WITH', 'e.user = u.id')
            ->andWhere('e.id IS NULL')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getAllUsers(string $searchTerm = '', int $page = 1, int $pageSize = 10, string $sort = 'lastName', string $direction = 'asc'): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.userType = :userType')
            ->setParameter('userType', 'customer'); // Only get regular users, not employees

        if (!empty($searchTerm)) {
            $qb->andWhere('u.firstName LIKE :term OR u.lastName LIKE :term OR u.email LIKE :term OR CONCAT(u.firstName, \' \', u.lastName) LIKE :term')
                ->setParameter('term', '%' . $searchTerm . '%');
        }

        // Calculate total for pagination
        $countQb = clone $qb;
        $total = $countQb->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        // Sort by the specified field
        $sortDirection = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $qb->orderBy('u.' . $sort, $sortDirection);

        // Add pagination
        $qb->setMaxResults($pageSize)
            ->setFirstResult(($page - 1) * $pageSize);

        $users = $qb->getQuery()->getResult();

        // Prepare data for the view
        $mappedUsers = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'gender' => $user->getGender(),
                'avatarUrl' => $user->getAvatarUrl(),
                'userType' => $user->getUserType(),
                'isAdmin' => $user->isAdmin(),
                'fullName' => $user->getFullName()
            ];
        }, $users);

        return [
            'items' => $mappedUsers,
            'total' => $total,
            'totalPages' => ceil($total / $pageSize)
        ];
    }

    /**
     * Get total number of users (customers only)
     */
    public function getTotalCount(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.userType = :userType')
            ->setParameter('userType', 'customer')
            ->getQuery()
            ->getSingleScalarResult();
    }

}

