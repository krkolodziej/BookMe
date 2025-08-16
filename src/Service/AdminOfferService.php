<?php

namespace App\Service;

use App\Entity\Offer;
use App\Entity\Service;
use App\Repository\OfferRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminOfferService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OfferRepository $offerRepository,
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
     * @param int $page
     * @param int $pageSize
     * @param string $sort
     * @param string $direction
     * @return array
     */
    public function getServiceOffers(
        Service $service,
        int $page = 1,
        int $pageSize = 10,
        string $sort = 'name',
        string $direction = 'asc'
    ): array {
        return $this->offerRepository->getServiceOffers($service, $page, $pageSize, $sort, $direction);
    }

    /**
     * @param Service $service
     * @return Offer
     */
    public function createNewOffer(Service $service): Offer
    {
        $offer = new Offer();
        $offer->setService($service);
        
        return $offer;
    }

    /**
     * @param int $id
     * @param Service $service
     * @return Offer
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function getOfferForEdit(int $id, Service $service): Offer
    {
        $offer = $this->offerRepository->find($id);

        if (!$offer) {
            throw new NotFoundHttpException('Nie znaleziono oferty o podanym identyfikatorze');
        }

        if ($offer->getService()->getId() !== $service->getId()) {
            throw new AccessDeniedException('Ta oferta nie należy do wybranego serwisu');
        }

        return $offer;
    }

    /**
     * @param Offer $offer
     * @return void
     */
    public function saveOffer(Offer $offer): void
    {
        $this->offerRepository->save($offer, true);
    }

    /**
     * @param Offer $offer
     * @return void
     */
    public function updateOffer(Offer $offer): void
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
    public function deleteOffer(int $id, Service $service, string $csrfToken): void
    {
        $offer = $this->getOfferForEdit($id, $service);
            
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete'.$offer->getId(), $csrfToken))) {
            throw new AccessDeniedException('Nieprawidłowy token CSRF');
        }

        $this->offerRepository->remove($offer, true);
    }
} 