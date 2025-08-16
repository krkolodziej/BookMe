<?php

namespace App\Repository;

use App\Entity\Offer;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    public function findOneByEncodedName(string $serviceEncodedName, string $offerEncodedName): ?Offer
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.service', 's')
            ->addSelect('s')
            ->andWhere('s.encodedName = :serviceEncodedName')
            ->andWhere('o.encodedName = :offerEncodedName')
            ->setParameter('serviceEncodedName', $serviceEncodedName)
            ->setParameter('offerEncodedName', $offerEncodedName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search offers by name
     *
     * @param string $term Search term
     * @return array Array of offer names
     */
    public function searchOffersByName(string $term): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.name')
            ->where('o.name LIKE :exact')
            ->orWhere('o.name LIKE :partial')
            ->setParameter('exact', $term . '%')
            ->setParameter('partial', '%' . $term . '%')
            ->orderBy('CASE 
            WHEN o.name LIKE :priority THEN 0 
            WHEN o.name LIKE :exact THEN 1 
            ELSE 2 
            END', 'ASC')
            ->setParameter('priority', $term)
            ->setMaxResults(10);

        $result = $qb->getQuery()->getResult();

        return array_map(function($item) {
            return $item['name'];
        }, $result);
    }

    public function save(Offer $offer, bool $flush = false): void
    {
        $this->getEntityManager()->persist($offer);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Offer $offer, bool $flush = false): void
    {
        $this->getEntityManager()->remove($offer);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getServiceOffers(
        Service $service,
        int $page = 1,
        int $pageSize = 10,
        string $sort = 'name',
        string $direction = 'asc'
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
            $validSortFields = ['name', 'price', 'duration'];
            $sortField = in_array($sort, $validSortFields) ? $sort : 'name';
            $sortDirection = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
            $qb->orderBy('o.' . $sortField, $sortDirection);

            $qb->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);

            $offers = $qb->getQuery()->getResult();

            return [
                'items' => $offers,
                'total' => $total,
                'totalPages' => ceil($total / $pageSize)
            ];

        } catch (\Exception $e) {
            throw new \RuntimeException('Error fetching offers: ' . $e->getMessage());
        }
    }
}