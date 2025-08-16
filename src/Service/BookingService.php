<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Offer;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\EmployeeRepository;
use App\Repository\OfferRepository;
use App\Repository\ServiceRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BookingService
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly OfferRepository $offerRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly AvailabilityService $availabilityService
    ) {
    }

    /**
     * @param string $serviceEncodedName
     * @param string $offerEncodedName
     * @return array
     * @throws NotFoundHttpException
     */
    public function getServiceAndOffer(string $serviceEncodedName, string $offerEncodedName): array
    {
        $service = $this->serviceRepository->findOneBy(['encodedName' => $serviceEncodedName]);
        
        if (!$service) {
            throw new NotFoundHttpException('Serwis nie został znaleziony.', null, 404);
        }
        
        $offer = $this->offerRepository->findOneBy([
            'encodedName' => $offerEncodedName,
            'service' => $service
        ]);
        
        if (!$offer) {
            throw new NotFoundHttpException('Usługa nie została znaleziona.', null, 404);
        }
        
        return [
            'service' => $service,
            'offer' => $offer
        ];
    }

    /**
     * @param User $user
     * @param Service $service
     * @param Offer $offer
     * @return Booking
     */
    public function createBooking(User $user, Service $service, Offer $offer): Booking
    {
        $booking = new Booking();
        $booking->setUser($user);
        $booking->setService($service);
        $booking->setOffer($offer);
        
        return $booking;
    }

    /**
     * @param Booking $booking
     * @return Booking
     */
    public function saveBooking(Booking $booking): Booking
    {
        $booking->setEndTime();
        $this->bookingRepository->save($booking, true);
        
        return $booking;
    }

    /**
     * @param int $id
     * @param User $user
     * @return Booking
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function getUserBooking(int $id, User $user): Booking
    {
        $booking = $this->bookingRepository->find($id);
        
        if (!$booking) {
            throw new NotFoundHttpException('Wizyta nie została znaleziona.', null, 404);
        }
        
        if ($booking->getUser() !== $user) {
            throw new AccessDeniedException('Nie masz uprawnień do edycji tej wizyty.');
        }
        
        return $booking;
    }

    /**
     * @param int $id
     * @return void
     * @throws NotFoundHttpException
     */
    public function deleteBooking(int $id): void
    {
        $booking = $this->bookingRepository->find($id);
        
        if (!$booking) {
            throw new NotFoundHttpException('Nie znaleziono wizyty', null, 404);
        }
        
        $this->bookingRepository->remove($booking, true);
    }

    /** 
     * @param string $serviceEncodedName
     * @param string $offerEncodedName
     * @param string $employeeId
     * @param string $date
     * @return array
     * @throws NotFoundHttpException
     */
    public function getAvailableSlots(string $serviceEncodedName, string $offerEncodedName, string $employeeId, string $date): array
    {
        $serviceAndOffer = $this->getServiceAndOffer($serviceEncodedName, $offerEncodedName);
        $service = $serviceAndOffer['service'];
        $offer = $serviceAndOffer['offer'];
        
        $employee = $this->employeeRepository->find($employeeId);
        
        if (!$employee) {
            throw new NotFoundHttpException('Nie znaleziono pracownika.', null, 404);
        }
        
        $dateObj = new \DateTime($date);
        
        return $this->availabilityService->getAvailableSlots(
            $service,
            $offer,
            $employee,
            $dateObj
        );
    }
} 