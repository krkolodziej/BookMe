<?php

namespace App\Service;

use App\Entity\Opinion;
use App\Entity\Service;
use App\Repository\OpinionRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminOpinionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OpinionRepository $opinionRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    /**
     * Pobiera serwis na podstawie zakodowanej nazwy.
     *
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
     * Pobiera opinie dla danego serwisu z paginacją i sortowaniem.
     *
     * @param Service $service
     * @param int $page
     * @param int $pageSize
     * @param string $sorts
     * @return array
     */
    public function getServiceOpinions(
        Service $service,
        int $page = 1,
        int $pageSize = 10,
        string $sorts = '-createdAt'
    ): array {
        return $this->opinionRepository->getServiceOpinions($service, $page, $pageSize, $sorts);
    }

    /**
     * Tworzy nową opinię dla danego serwisu.
     *
     * @param Service $service
     * @return Opinion
     */
    public function createOpinion(Service $service): Opinion
    {
        $opinion = new Opinion();
        $opinion->setService($service);
        $opinion->setCreatedAt(new \DateTimeImmutable());
        
        return $opinion;
    }

    /**
     * Pobiera opinię do edycji i weryfikuje czy należy do określonego serwisu.
     *
     * @param int $id
     * @param Service $service
     * @return Opinion
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function getOpinionForEdit(int $id, Service $service): Opinion
    {
        $opinion = $this->opinionRepository->find($id);

        if (!$opinion) {
            throw new NotFoundHttpException('Nie znaleziono opinii o podanym identyfikatorze');
        }

        // Sprawdź czy opinia należy do serwisu
        if ($opinion->getService()->getId() !== $service->getId()) {
            throw new AccessDeniedException('Ta opinia nie należy do wybranego serwisu');
        }

        return $opinion;
    }

    /**
     * Zapisuje opinię w bazie danych.
     *
     * @param Opinion $opinion
     * @return void
     */
    public function saveOpinion(Opinion $opinion): void
    {
        $this->opinionRepository->save($opinion, true);
    }

    /**
     * Aktualizuje opinię w bazie danych.
     *
     * @param Opinion $opinion
     * @return void
     */
    public function updateOpinion(Opinion $opinion): void
    {
        $this->entityManager->flush();
    }

    /**
     *
     * @param int $id
     * @param Service $service
     * @param string $csrfToken
     * @return void
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function deleteOpinion(int $id, Service $service, string $csrfToken): void
    {
        $opinion = $this->getOpinionForEdit($id, $service);

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete'.$opinion->getId(), $csrfToken))) {
            throw new AccessDeniedException('Nieprawidłowy token CSRF');
        }

        $this->opinionRepository->remove($opinion, true);
    }
} 