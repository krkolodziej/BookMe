<?php

namespace App\Repository;

use App\Entity\Opinion;
use App\Entity\Service;
use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OpinionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Opinion::class);
    }

    public function save(Opinion $opinion, bool $flush = false): void
    {
        $this->getEntityManager()->persist($opinion);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Opinion $opinion, bool $flush = false): void
    {
        $this->getEntityManager()->remove($opinion);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByBookingId(int $bookingId): ?Opinion
    {
        return $this->createQueryBuilder('o')
            ->where('o.bookingId = :bookingId')
            ->setParameter('bookingId', $bookingId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getServiceOpinions(
        Service $service,
        int $page = 1,
        int $pageSize = 10,
        string $sorts = '-createdAt'
    ): array {
        try {
            $totalQb = $this->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->where('o.service = :service')
                ->setParameter('service', $service);

            $total = $totalQb->getQuery()->getSingleScalarResult();

            $qb = $this->createQueryBuilder('o')
                ->where('o.service = :service')
                ->setParameter('service', $service);
            $qb->leftJoin('o.booking', 'b')
                ->leftJoin('b.user', 'u')
                ->leftJoin('b.employee', 'e')
                ->leftJoin('b.offer', 'serviceOffer')
                ->addSelect('b', 'u', 'e', 'serviceOffer');

            if ($sorts === '-createdAt') {
                $qb->orderBy('o.createdAt', 'DESC');
            } elseif ($sorts === 'createdAt') {
                $qb->orderBy('o.createdAt', 'ASC');
            }

            $qb->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);

            $items = $qb->getQuery()->getResult();
            $mappedItems = array_map(function($opinion) {
                $booking = $opinion->getBooking();
                return [
                    'id' => $opinion->getId(),
                    'rating' => $opinion->getRating(),
                    'content' => $opinion->getContent(),
                    'firstName' => $booking->getUser()->getFirstName(),
                    'lastName' => $booking->getUser()->getLastName(),
                    'offerName' => $booking->getOffer()->getName(),
                    'employeeFullName' => $booking->getEmployee()
                        ? sprintf('%s %s',
                            $booking->getEmployee()->getUser()->getFirstName(),
                            $booking->getEmployee()->getUser()->getLastName()
                        )
                        : null,
                    'createdAt' => $opinion->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }, $items);

            return [
                'items' => $mappedItems,
                'total' => $total,
                'totalPages' => ceil($total / $pageSize)
            ];

        } catch (\Exception $e) {
            throw new \RuntimeException('Error fetching opinions: ' . $e->getMessage());
        }
    }

    public function findOpinionsByEmployee(int $employeeId): array
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'b', 'u')
            ->join('o.booking', 'b')
            ->join('b.user', 'u')
            ->where('b.employee = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}