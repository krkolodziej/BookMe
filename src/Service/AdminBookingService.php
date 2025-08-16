<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Service;
use App\Repository\BookingRepository;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminBookingService
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly AvailabilityService $availabilityService
    ) {
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
            throw new NotFoundHttpException('Service not found.');
        }

        return $service;
    }

    /**
     *
     * @param string $encodedName
     * @return array
     * @throws NotFoundHttpException
     */
    public function getServiceBookings(string $encodedName): array
    {
        $this->getServiceByEncodedName($encodedName);
        return $this->bookingRepository->findUpcomingByService($encodedName);
    }

    /**
     *
     * @param Service $service
     * @return Booking
     */
    public function createBooking(Service $service): Booking
    {
        $booking = new Booking();
        $booking->setService($service);
        return $booking;
    }

    /**
     *
     * @param string $encodedName
     * @return array
     */
    public function getEmployeesForService(string $encodedName): array
    {
        return $this->employeeRepository->findByServiceEncodedName($encodedName);
    }

    /**
     *
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
     *
     * @param int $id
     * @param Service $service
     * @return Booking
     * @throws NotFoundHttpException
     */
    public function getBookingForEdit(int $id, Service $service): Booking
    {
        $booking = $this->bookingRepository->find($id);

        if (!$booking) {
            throw new NotFoundHttpException('Visit not found.');
        }

        if ($booking->getService()->getId() !== $service->getId()) {
            throw new NotFoundHttpException('The visit does not belong to this service.');
        }

        return $booking;
    }

    /**
     *
     * @param int $id
     * @param Service $service
     * @return void
     * @throws NotFoundHttpException
     */
    public function deleteBooking(int $id, Service $service): void
    {
        $booking = $this->getBookingForEdit($id, $service);
        $this->bookingRepository->remove($booking, true);
    }

    /**
     *
     * @param Service $service
     * @param string $offerId
     * @param string $employeeId
     * @param string $date
     * @return array
     * @throws BadRequestHttpException
     */
    public function getAvailableTimeSlots(Service $service, string $offerId, string $employeeId, string $date): array
    {
        $employee = $this->employeeRepository->find($employeeId);
        $offer = $service->getOffers()->filter(fn ($o) => $o->getId() == $offerId)->first();

        if (!$employee || !$offer) {
            throw new BadRequestHttpException('Employee or offer not found.');
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