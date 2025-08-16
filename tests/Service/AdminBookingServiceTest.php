<?php

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Entity\Employee;
use App\Entity\Offer;
use App\Entity\Service;
use App\Repository\BookingRepository;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use App\Service\AdminBookingService;
use App\Service\AvailabilityService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminBookingServiceTest extends TestCase
{
    private $bookingRepository;
    private $serviceRepository;
    private $employeeRepository;
    private $availabilityService;
    private $adminBookingService;

    protected function setUp(): void
    {
        $this->bookingRepository = $this->createMock(BookingRepository::class);
        $this->serviceRepository = $this->createMock(ServiceRepository::class);
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->availabilityService = $this->createMock(AvailabilityService::class);

        $this->adminBookingService = new AdminBookingService(
            $this->bookingRepository,
            $this->serviceRepository,
            $this->employeeRepository,
            $this->availabilityService
        );
    }

    public function testGetServiceByEncodedNameSuccess()
    {
        $encodedName = 'relaxing-massage';
        $service = $this->createMock(Service::class);

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $encodedName])
            ->willReturn($service);

        $result = $this->adminBookingService->getServiceByEncodedName($encodedName);

        $this->assertSame($service, $result);
    }

    public function testGetServiceByEncodedNameNotFound()
    {
        $encodedName = 'non-existent-service';

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $encodedName])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Service not found.');

        $this->adminBookingService->getServiceByEncodedName($encodedName);
    }

    public function testGetServiceBookings()
    {
        $encodedName = 'relaxing-massage';
        $service = $this->createMock(Service::class);
        $bookings = [
            $this->createMock(Booking::class),
            $this->createMock(Booking::class)
        ];

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $encodedName])
            ->willReturn($service);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findUpcomingByService')
            ->with($encodedName)
            ->willReturn($bookings);

        $result = $this->adminBookingService->getServiceBookings($encodedName);

        $this->assertSame($bookings, $result);
    }

    public function testCreateBooking()
    {
        $service = $this->createMock(Service::class);

        $booking = $this->adminBookingService->createBooking($service);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertSame($service, $booking->getService());
    }

    public function testGetEmployeesForService()
    {
        $encodedName = 'relaxing-massage';
        $employees = [
            $this->createMock(Employee::class),
            $this->createMock(Employee::class)
        ];

        $this->employeeRepository
            ->expects($this->once())
            ->method('findByServiceEncodedName')
            ->with($encodedName)
            ->willReturn($employees);

        $result = $this->adminBookingService->getEmployeesForService($encodedName);

        $this->assertSame($employees, $result);
    }

    public function testSaveBooking()
    {
        $booking = $this->createMock(Booking::class);

        $booking->expects($this->once())
            ->method('setEndTime')
            ->willReturn($booking);

        $this->bookingRepository
            ->expects($this->once())
            ->method('save')
            ->with($booking, true);

        $result = $this->adminBookingService->saveBooking($booking);

        $this->assertSame($booking, $result);
    }

    public function testGetBookingForEditSuccess()
    {
        $bookingId = 1;
        $serviceId = 2;
        $service = $this->createMock(Service::class);
        $service->method('getId')->willReturn($serviceId);
        
        $booking = $this->createMock(Booking::class);
        $bookingService = $this->createMock(Service::class);
        $bookingService->method('getId')->willReturn($serviceId);
        $booking->method('getService')->willReturn($bookingService);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $result = $this->adminBookingService->getBookingForEdit($bookingId, $service);

        $this->assertSame($booking, $result);
    }

    public function testGetBookingForEditNotFound()
    {
        $bookingId = 999;
        $service = $this->createMock(Service::class);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Visit not found.');

        $this->adminBookingService->getBookingForEdit($bookingId, $service);
    }

    public function testGetBookingForEditNotInService()
    {
        $bookingId = 1;
        $serviceId = 2;
        $otherServiceId = 3;
        
        $service = $this->createMock(Service::class);
        $service->method('getId')->willReturn($serviceId);
        
        $booking = $this->createMock(Booking::class);
        $otherService = $this->createMock(Service::class);
        $otherService->method('getId')->willReturn($otherServiceId);
        $booking->method('getService')->willReturn($otherService);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The visit does not belong to this service.');

        $this->adminBookingService->getBookingForEdit($bookingId, $service);
    }

    public function testDeleteBooking()
    {
        $bookingId = 1;
        $serviceId = 2;
        $service = $this->createMock(Service::class);
        $service->method('getId')->willReturn($serviceId);
        
        $booking = $this->createMock(Booking::class);
        $bookingService = $this->createMock(Service::class);
        $bookingService->method('getId')->willReturn($serviceId);
        $booking->method('getService')->willReturn($bookingService);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->bookingRepository
            ->expects($this->once())
            ->method('remove')
            ->with($booking, true);

        $this->adminBookingService->deleteBooking($bookingId, $service);
    }

    public function testGetAvailableTimeSlots()
    {
        $service = $this->createMock(Service::class);
        $offerId = '1';
        $employeeId = '2';
        $date = '2023-06-01';
        
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        
        $offers = new ArrayCollection([$offer]);
        $offer->method('getId')->willReturn(1);
        
        $service->method('getOffers')->willReturn($offers);
        
        $expectedSlots = [
            new \DateTime('2023-06-01 10:00:00'),
            new \DateTime('2023-06-01 11:00:00'),
            new \DateTime('2023-06-01 12:00:00')
        ];

        $this->employeeRepository
            ->expects($this->once())
            ->method('find')
            ->with($employeeId)
            ->willReturn($employee);

        $this->availabilityService
            ->expects($this->once())
            ->method('getAvailableSlots')
            ->with(
                $service,
                $offer,
                $employee,
                $this->callback(function($dateObj) use ($date) {
                    return $dateObj->format('Y-m-d') === $date;
                })
            )
            ->willReturn($expectedSlots);

        $result = $this->adminBookingService->getAvailableTimeSlots($service, $offerId, $employeeId, $date);

        $this->assertSame($expectedSlots, $result);
    }

    public function testGetAvailableTimeSlotsNotFound()
    {
        $service = $this->createMock(Service::class);
        $offerId = '999';
        $employeeId = '999';
        $date = '2023-06-01';
        
        $offers = new ArrayCollection([]);
        $service->method('getOffers')->willReturn($offers);

        $this->employeeRepository
            ->expects($this->once())
            ->method('find')
            ->with($employeeId)
            ->willReturn(null);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Employee or offer not found.');

        $this->adminBookingService->getAvailableTimeSlots($service, $offerId, $employeeId, $date);
    }
}
