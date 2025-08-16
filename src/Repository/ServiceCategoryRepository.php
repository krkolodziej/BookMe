<?php

namespace App\Repository;

use App\Entity\ServiceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceCategory::class);
    }


    public function getByEncodedName(string $encodedName, string $searchTerm = ''): ?ServiceCategory
    {
        if ($encodedName === 'inne') {
            return $this->getOtherCategory($searchTerm);
        }

        // Najpierw znajdujemy kategorię po encodedName
        $category = $this->createQueryBuilder('sc')
            ->select('sc', 's') // Usuwamy 'o' z select
            ->leftJoin('sc.services', 's')
            ->where('sc.encodedName = :encodedName')
            ->setParameter('encodedName', $encodedName)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$category) {
            return null;
        }

        // Jeśli mamy searchTerm, filtrujemy serwisy w tej kategorii
        if ($searchTerm) {
            $searchTerm = strtolower($searchTerm);
            $qb = $this->getEntityManager()->createQueryBuilder();

            $services = $qb
                ->select('s')
                ->from('App\Entity\Service', 's')
                ->where('s.serviceCategory = :category')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('LOWER(s.name)', ':searchTerm'),
                        $qb->expr()->like('LOWER(s.street)', ':searchTerm'),
                        $qb->expr()->like('LOWER(s.city)', ':searchTerm')
                    )
                )
                ->setParameter('category', $category)
                ->setParameter('searchTerm', '%' . $searchTerm . '%')
                ->getQuery()
                ->getResult();

            // Czyścimy wszystkie serwisy i dodajemy tylko te przefiltrowane
            $category->getServices()->clear();
            foreach ($services as $service) {
                if ($service) {
                    $this->calculateServiceRatings($service);
                    $category->addService($service);
                }
            }
        } else {
            // Jeśli nie ma searchTerm, pobieramy wszystkie serwisy dla kategorii
            // i obliczamy ich oceny
            foreach ($category->getServices() as $service) {
                if ($service) {
                    $this->calculateServiceRatings($service);
                }
            }
        }

        return $category;
    }

    private function calculateServiceRatings($service)
    {
        if (!$service) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $result = $qb->select('COUNT(o) as count, SUM(o.rating) as sum')
            ->from('App\Entity\Opinion', 'o')
            ->where('o.service = :service')
            ->setParameter('service', $service)
            ->getQuery()
            ->getOneOrNullResult();

        $opinionsCount = $result ? $result['count'] : 0;
        $ratingsSum = $result ? $result['sum'] : 0;

        $service->setOpinionsCount($opinionsCount);
        $service->setAverageRating($opinionsCount > 0 ? round($ratingsSum / $opinionsCount, 1) : 0);
    }

    private function getOtherCategory(string $searchTerm)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $servicesWithoutCategory = $qb
            ->select('s', 'o')
            ->from('App\Entity\Service', 's')
            ->leftJoin('s.opinions', 'o')
            ->where('s.serviceCategory IS NULL');

        if ($searchTerm) {
            $searchTerm = strtolower($searchTerm);
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(s.name)', ':searchTerm'),
                    $qb->expr()->like('LOWER(s.street)', ':searchTerm'),
                    $qb->expr()->like('LOWER(s.city)', ':searchTerm')
                )
            )
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        $servicesWithoutCategory = $qb->getQuery()->getResult();

        $category = new ServiceCategory();
        $category->setName('Inne');

        foreach ($servicesWithoutCategory as $service) {
            $this->calculateServiceRatings($service);
            $category->addService($service);
        }

        return $category;
    }

    public function searchCategoriesByName(string $searchTerm): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

        /**
     * Get total number of categories
     */
    public function getTotalCount(): int
    {
        return $this->createQueryBuilder('sc')
            ->select('COUNT(sc.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}