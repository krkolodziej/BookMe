<?php

namespace App\Repository;

use App\Entity\Service;
use App\Entity\ServiceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }


    public function save(Service $service, bool $flush = false): void
    {
        if ($this->findOneBy(['name' => $service->getName()]) && !$service->getId()) {
            throw new \Exception('Nazwa serwisu jest już zajęta.');
        }

        $this->getEntityManager()->persist($service);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Service $service, bool $flush = false): void
    {
        $this->getEntityManager()->remove($service);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function findRecommendedServices(): array
    {
        $qb = $this->createQueryBuilder('s');

        $result = $qb
            ->select('s as service')
            ->addSelect('s.id')
            ->addSelect('s.name')
            ->addSelect('s.imageUrl')
            ->addSelect('COUNT(o.id) as opinionsCount')
            ->addSelect('COALESCE(AVG(o.rating), 0) as averageRating')
            ->addSelect('(COALESCE(AVG(o.rating), 0) * COUNT(o.id)) as score')
            ->leftJoin('App\Entity\Opinion', 'o', 'WITH', 'o.service = s')
            ->groupBy('s.id')
            ->orderBy('score', 'DESC')
            ->setMaxResults(9)
            ->getQuery()
            ->getResult();

        return array_map(function($row) {
            /** @var Service $service */
            $service = $row['service'];
            $service->setOpinionsCount($row['opinionsCount']);
            $service->setAverageRating(round($row['averageRating'], 1));
            return $service;
        }, $result);
    }


    public function findByEncodedNameWithDetails(string $encodedName): ?Service
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.offers', 'o')
            ->leftJoin('s.openingHours', 'oh')
            ->leftJoin('s.employees', 'e')
            ->leftJoin('e.user', 'u')
            ->leftJoin('s.opinions', 'op')
            ->addSelect('o', 'oh', 'e', 'u', 'op')
            ->where('s.encodedName = :encodedName')
            ->setParameter('encodedName', $encodedName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search for unique cities in services
     *
     * @param string $term Search term
     * @return array Array of city names
     */
    public function searchCities(string $term): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('DISTINCT s.city')
            ->where('s.city LIKE :exact')
            ->orWhere('s.city LIKE :partial')
            ->setParameter('exact', $term . '%')
            ->setParameter('partial', '%' . $term . '%')
            ->orderBy('CASE 
            WHEN s.city LIKE :priority THEN 0 
            WHEN s.city LIKE :exact THEN 1 
            ELSE 2 
            END', 'ASC')
            ->setParameter('priority', $term)
            ->setMaxResults(10);

        $result = $qb->getQuery()->getResult();

        // Extract just the city names from the result
        return array_map(function($item) {
            return $item['city'];
        }, $result);
    }

    /**
     * Search services by offer name and/or city
     *
     * @param string $searchTerm Offer name search term
     * @param string $city City search term
     * @return array Array of Service entities
     */
    public function searchServicesByOfferAndCity(string $searchTerm, string $city): array
    {
        $qb = $this->createQueryBuilder('s');

        // Add join with offers if searchTerm is provided
        if (!empty($searchTerm)) {
            $qb->leftJoin('s.offers', 'o')
                ->where('o.name LIKE :searchTerm OR s.name LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // Add city condition if city is provided
        if (!empty($city)) {
            if (!empty($searchTerm)) {
                $qb->andWhere('s.city LIKE :city');
            } else {
                $qb->where('s.city LIKE :city');
            }
            $qb->setParameter('city', '%' . $city . '%');
        }

        // Add group by to eliminate duplicates
        $qb->groupBy('s.id');

        $services = $qb->getQuery()->getResult();

        // Calculate opinions count and average rating for each service
        foreach ($services as $service) {
            $this->calculateServiceRatings($service);
        }

        return $services;
    }

    /**
     * Calculate and set opinions count and average rating for a service
     *
     * @param Service $service
     * @return void
     */
    private function calculateServiceRatings(Service $service): void
    {
        $opinions = $service->getOpinions();
        $opinionsCount = count($opinions);

        $service->setOpinionsCount($opinionsCount);

        if ($opinionsCount > 0) {
            $sum = 0;
            foreach ($opinions as $opinion) {
                $sum += $opinion->getRating();
            }
            $averageRating = $sum / $opinionsCount;
            $service->setAverageRating($averageRating);
        } else {
            $service->setAverageRating(0);
        }

    }

    public function searchServicesByName(string $searchTerm): array
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where('s.name LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('s.name', 'ASC');

        $services = $qb->getQuery()->getResult();

        foreach ($services as $service) {
            $this->calculateServiceRatings($service);
        }

        return $services;
    }

        /**
     * Get total number of services
     */
    public function getTotalCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get number of active services
     */
    public function getActiveCount(): int
    {
        return $this->getTotalCount(); // lub z warunkiem active = true jeśli masz takie pole
    }
}