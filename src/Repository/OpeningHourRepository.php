<?php

namespace App\Repository;

use App\Entity\OpeningHour;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OpeningHourRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpeningHour::class);
    }

    public function findByService(Service $service): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.service = :service')
            ->setParameter('service', $service)
            ->orderBy('CASE 
                WHEN o.dayOfWeek = \'Monday\' THEN 1 
                WHEN o.dayOfWeek = \'Tuesday\' THEN 2 
                WHEN o.dayOfWeek = \'Wednesday\' THEN 3 
                WHEN o.dayOfWeek = \'Thursday\' THEN 4 
                WHEN o.dayOfWeek = \'Friday\' THEN 5 
                WHEN o.dayOfWeek = \'Saturday\' THEN 6 
                WHEN o.dayOfWeek = \'Sunday\' THEN 7 
                END', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByServiceEncodedName(string $encodedName): array
    {
        return $this->createQueryBuilder('oh')
            ->join('oh.service', 's')
            ->where('s.encodedName = :encodedName')
            ->setParameter('encodedName', $encodedName)
            ->orderBy("CASE 
                        WHEN oh.dayOfWeek = 'Poniedziałek' THEN 1 
                        WHEN oh.dayOfWeek = 'Wtorek' THEN 2 
                        WHEN oh.dayOfWeek = 'Środa' THEN 3 
                        WHEN oh.dayOfWeek = 'Czwartek' THEN 4 
                        WHEN oh.dayOfWeek = 'Piątek' THEN 5 
                        WHEN oh.dayOfWeek = 'Sobota' THEN 6 
                        WHEN oh.dayOfWeek = 'Niedziela' THEN 7 
                        ELSE 8 END", 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get days that already have opening hours for a service
     */
    public function getExistingDaysForService(int $serviceId, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('oh')
            ->select('oh.dayOfWeek')
            ->where('oh.service = :serviceId')
            ->setParameter('serviceId', $serviceId);

        if ($excludeId !== null) {
            $qb->andWhere('oh.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        $result = $qb->getQuery()->getResult();

        return array_map(function($item) {
            return $item['dayOfWeek'];
        }, $result);
    }

}