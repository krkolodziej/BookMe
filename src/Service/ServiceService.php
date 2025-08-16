<?php

namespace App\Service;

use App\Entity\Service;
use App\Repository\ServiceImageRepository;
use App\Repository\ServiceRepository;

class ServiceService
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository,
        private readonly ServiceImageRepository $serviceImageRepository
    ) {
    }

    /** 
     * @param string $encodedName
     * @return Service|null
     */
    public function getServiceByEncodedName(string $encodedName): ?Service
    {
        return $this->serviceRepository->findByEncodedNameWithDetails($encodedName);
    }

    /** 
     * @param int $serviceId
     * @return array
     */
    public function getServiceImages(int $serviceId): array
    {
        return $this->serviceImageRepository->findByServiceId($serviceId);
    }

    /** 
     * @param Service $service
     * @return array
     */
    public function calculateAverageRating(Service $service): array
    {
        if ($service->getOpinions()->count() > 0) {
            $averageRating = round(array_reduce(
                    $service->getOpinions()->toArray(),
                    fn($carry, $opinion) => $carry + $opinion->getRating(),
                    0
                ) / $service->getOpinions()->count(), 1);
            $opinionsCount = $service->getOpinions()->count();
        } else {
            $averageRating = 0.0;
            $opinionsCount = 0;
        }

        return [
            'averageRating' => $averageRating,
            'opinionsCount' => $opinionsCount
        ];
    }
} 