<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @param \App\Entity\Employee $employee
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countUnreadByEmployee(\App\Entity\Employee $employee): int
    {
        return $this->createQueryBuilder('n')
            ->select('count(n.id)')
            ->andWhere('n.employee = :employee')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('employee', $employee)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}