<?php

namespace App\Repository;

use App\Entity\ServiceImage;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceImage::class);
    }


    public function findByServiceId(int $serviceId): array
    {
        return $this->createQueryBuilder('si')
            ->where('si.service = :serviceId')
            ->setParameter('serviceId', $serviceId)
            ->getQuery()
            ->getResult();
    }

    public function save(ServiceImage $serviceImage, bool $flush = false): void
    {
        $this->getEntityManager()->persist($serviceImage);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ServiceImage $serviceImage, bool $flush = false): void
    {
        $this->getEntityManager()->remove($serviceImage);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByService(Service $service): array
    {
        return $this->createQueryBuilder('si')
            ->where('si.service = :service')
            ->setParameter('service', $service)
            ->getQuery()
            ->getResult();
    }

    public function countByService(Service $service): int
    {
        return $this->createQueryBuilder('si')
            ->select('COUNT(si.id)')
            ->where('si.service = :service')
            ->setParameter('service', $service)
            ->getQuery()
            ->getSingleScalarResult();
    }
}