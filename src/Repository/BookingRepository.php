<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Employee;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findConflictingBookings(
        Employee $employee,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        return $this->createQueryBuilder('b')
            ->where('b.employee = :employee')
            ->andWhere('b.startTime BETWEEN :start AND :end')
            ->setParameter('employee', $employee)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function findUserBookings(
        User $user,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        return $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.startTime BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function save(Booking $booking, bool $flush = false): void
    {
        $this->getEntityManager()->persist($booking);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Booking $booking, bool $flush = false): void
    {
        if ($booking->getOpinion() !== null) {
            $this->getEntityManager()->remove($booking->getOpinion());
        }

        $this->getEntityManager()->remove($booking);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByEmployee(int $employeeId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.employee = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('b.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('b.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingByService(string $serviceEncodedName): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('b')
            ->innerJoin('b.service', 's')
            ->innerJoin('b.user', 'u')
            ->leftJoin('b.employee', 'e')
            ->innerJoin('b.offer', 'o')
            ->where('s.encodedName = :serviceEncodedName')
            ->andWhere('b.startTime > :now')
            ->setParameter('serviceEncodedName', $serviceEncodedName)
            ->setParameter('now', $now)
            ->orderBy('b.startTime', 'ASC')
            ->addSelect('s', 'u', 'e', 'o')
            ->getQuery()
            ->getResult();
    }
}