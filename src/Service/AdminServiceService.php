<?php

namespace App\Service;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminServiceService
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository
    ) {
    }

    /**
     *
     * @return array
     */
    public function getAllServices(): array
    {
        return $this->serviceRepository->findAll();
    }

    /**
     *
     * @param string $searchTerm
     * @return array
     */
    public function searchServicesByName(string $searchTerm): array
    {
        return $this->serviceRepository->searchServicesByName($searchTerm);
    }

    /**
     *
     * @param string $encodedName
     * @return Service
     * @throws NotFoundHttpException
     */
    public function getServiceByEncodedNameWithDetails(string $encodedName): Service
    {
        $service = $this->serviceRepository->findByEncodedNameWithDetails($encodedName);

        if (!$service) {
            throw new NotFoundHttpException('Serwis nie został znaleziony.');
        }

        return $service;
    }

    /**
     *
     * @param string $encodedName
     * @return Service
     * @throws NotFoundHttpException
     */
    public function getServiceByEncodedName(string $encodedName): Service
    {
        $service = $this->serviceRepository->findOneBy(['encodedName' => $encodedName]);

        if (!$service) {
            throw new NotFoundHttpException('Serwis nie został znaleziony.');
        }

        return $service;
    }

    /**
     *
     * @param Service $service
     * @param bool $flush
     * @return void
     */
    public function saveService(Service $service, bool $flush = true): void
    {
        $this->serviceRepository->save($service, $flush);
    }

    /**
     *
     * @param Service $service
     * @param bool $flush
     * @return void
     */
    public function removeService(Service $service, bool $flush = true): void
    {
        $this->serviceRepository->remove($service, $flush);
    }
} 