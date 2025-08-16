<?php

namespace App\Service;

use App\Entity\Service;
use App\Entity\ServiceImage;
use App\Repository\ServiceImageRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminServiceImageService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceImageRepository $serviceImageRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    /**
     * @param string $encodedName
     * @return Service
     * @throws NotFoundHttpException
     */
    public function getServiceByEncodedName(string $encodedName): Service
    {
        $service = $this->serviceRepository->findOneBy(['encodedName' => $encodedName]);

        if (!$service) {
            throw new NotFoundHttpException('Nie znaleziono serwisu o podanym identyfikatorze');
        }

        return $service;
    }

    /**
     * @param Service $service
     * @return array
     */
    public function getServiceImages(Service $service): array
    {
        return $this->serviceImageRepository->findBy(['service' => $service]);
    }

    /**
     * @param Service $service
     * @return ServiceImage
     */
    public function createServiceImage(Service $service): ServiceImage
    {
        $serviceImage = new ServiceImage();
        $serviceImage->setService($service);

        return $serviceImage;
    }

    /**
     * @param ServiceImage $serviceImage
     * @param bool $flush
     * @return void
     */
    public function saveServiceImage(ServiceImage $serviceImage, bool $flush = true): void
    {
        $this->serviceImageRepository->save($serviceImage, $flush);
    }

    /**
     * @param int $id
     * @param Service $service
     * @return ServiceImage
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function getServiceImageForEdit(int $id, Service $service): ServiceImage
    {
        $serviceImage = $this->serviceImageRepository->find($id);

        if (!$serviceImage) {
            throw new NotFoundHttpException('Nie znaleziono zdjęcia o podanym identyfikatorze');
        }

        if ($serviceImage->getService()->getId() !== $service->getId()) {
            throw new AccessDeniedException('To zdjęcie nie należy do wybranego serwisu');
        }

        return $serviceImage;
    }

    /**
     * @param ServiceImage $serviceImage
     * @return void
     */
    public function updateServiceImage(ServiceImage $serviceImage): void
    {
        $this->entityManager->flush();
    }

    /**
     * @param int $id
     * @param Service $service
     * @param string $csrfToken
     * @return void
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function deleteServiceImage(int $id, Service $service, string $csrfToken): void
    {
        $serviceImage = $this->getServiceImageForEdit($id, $service);
                                    
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete'.$serviceImage->getId(), $csrfToken))) {
            throw new AccessDeniedException('Nieprawidłowy token CSRF');
        }

        $this->serviceImageRepository->remove($serviceImage, true);
    }
}