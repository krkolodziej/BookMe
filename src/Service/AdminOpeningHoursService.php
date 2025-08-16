<?php

namespace App\Service;

use App\Entity\OpeningHour;
use App\Entity\Service;
use App\Repository\OpeningHourRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminOpeningHoursService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceRepository $serviceRepository,
        private readonly OpeningHourRepository $openingHourRepository,
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
            throw new NotFoundHttpException('Serwis nie został znaleziony');
        }

        return $service;
    }

    /**
     * @param string $encodedName
     * @return array
     */
    public function getOpeningHoursForService(string $encodedName): array
    {
        return $this->openingHourRepository->findByServiceEncodedName($encodedName);
    }

    /**
     * @param int $serviceId
     * @param int|null $excludeOpeningHourId
     * @return array
     */
    public function getExistingDaysForService(int $serviceId, ?int $excludeOpeningHourId = null): array
    {
        return $this->openingHourRepository->getExistingDaysForService($serviceId, $excludeOpeningHourId);
    }

    /**
     * @param array $openingHours
     * @return array
     */
    public function getAvailableDays(array $openingHours): array
    {
        $allDays = [
            'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota', 'Niedziela'
        ];

        $existingDays = array_map(function ($hour) {
            return $hour->getDayOfWeek();
        }, $openingHours);

        return array_diff($allDays, $existingDays);
    }

    /**
     * @param Service $service
     * @return OpeningHour
     */
    public function createOpeningHour(Service $service): OpeningHour
    {
        $openingHour = new OpeningHour();
        $openingHour->setService($service);
        
        return $openingHour;
    }

    /**
     * @param Service $service
     * @param string $dayOfWeek
     * @return bool
     */
    public function isDayAlreadyDefined(Service $service, string $dayOfWeek): bool
    {
        $existingOpeningHour = $this->openingHourRepository->findOneBy([
            'service' => $service,
            'dayOfWeek' => $dayOfWeek
        ]);

        return $existingOpeningHour !== null;
    }

    /**
     * @param OpeningHour $openingHour
     * @return void
     * @throws BadRequestHttpException
     */
    public function saveOpeningHour(OpeningHour $openingHour): void
    {
        $dayOfWeek = $openingHour->getDayOfWeek();
        $service = $openingHour->getService();

        if ($this->isDayAlreadyDefined($service, $dayOfWeek)) {
            throw new BadRequestHttpException("Godziny otwarcia dla dnia {$dayOfWeek} już istnieją.");
        }

        $this->entityManager->persist($openingHour);
        $this->entityManager->flush();
    }

    /**     
     * @param int $id
     * @param Service $service
     * @return OpeningHour
     * @throws NotFoundHttpException
     */
    public function getOpeningHourForEdit(int $id, Service $service): OpeningHour
    {
        $openingHour = $this->openingHourRepository->find($id);

        if (!$openingHour || $openingHour->getService()->getId() !== $service->getId()) {
            throw new NotFoundHttpException('Nie znaleziono godzin otwarcia');
        }

        return $openingHour;
    }

    /**
     * @param OpeningHour $openingHour
     * @param string $originalDay
     * @return void
     * @throws BadRequestHttpException
     */
    public function updateOpeningHour(OpeningHour $openingHour, string $originalDay): void
    {
        $newDay = $openingHour->getDayOfWeek();
        $service = $openingHour->getService();

        if ($originalDay !== $newDay && $this->isDayAlreadyDefined($service, $newDay)) {
            throw new BadRequestHttpException("Godziny otwarcia dla dnia {$newDay} już istnieją.");
        }

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
    public function deleteOpeningHour(int $id, Service $service, string $csrfToken): void
    {
        $openingHour = $this->getOpeningHourForEdit($id, $service);

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete'.$openingHour->getId(), $csrfToken))) {
            throw new AccessDeniedException('Nieprawidłowy token CSRF');
        }

        $this->entityManager->remove($openingHour);
        $this->entityManager->flush();
    }
} 