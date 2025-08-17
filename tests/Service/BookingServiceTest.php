<?php

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Entity\Offer;
use App\Entity\Service;
use App\Entity\User;
use App\Entity\Employee;
use App\Repository\BookingRepository;
use App\Repository\EmployeeRepository;
use App\Repository\OfferRepository;
use App\Repository\ServiceRepository;
use App\Service\AvailabilityService;
use App\Service\BookingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BookingServiceTest extends TestCase
{
    private $bookingRepository;
    private $serviceRepository;
    private $offerRepository;
    private $employeeRepository;
    private $availabilityService;
    private $bookingService;

    protected function setUp(): void
    {
        $this->bookingRepository = $this->createMock(BookingRepository::class);
        $this->serviceRepository = $this->createMock(ServiceRepository::class);
        $this->offerRepository = $this->createMock(OfferRepository::class);
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->availabilityService = $this->createMock(AvailabilityService::class);

        $this->bookingService = new BookingService(
            $this->bookingRepository,
            $this->serviceRepository,
            $this->offerRepository,
            $this->employeeRepository,
            $this->availabilityService
        );
    }

    public function testGetServiceAndOfferSuccess()
    {
        // Przygotowanie danych
        $serviceEncodedName = 'masaz-relaksacyjny';
        $offerEncodedName = 'masaz-relaksacyjny-60min';
        
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $serviceEncodedName])
            ->willReturn($service);

        $this->offerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'encodedName' => $offerEncodedName,
                'service' => $service
            ])
            ->willReturn($offer);

        $result = $this->bookingService->getServiceAndOffer($serviceEncodedName, $offerEncodedName);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('service', $result);
        $this->assertArrayHasKey('offer', $result);
        $this->assertSame($service, $result['service']);
        $this->assertSame($offer, $result['offer']);
    }

    public function testGetServiceAndOfferWhenServiceNotFound()
    {
        $serviceEncodedName = 'nieistniejacy-serwis';
        $offerEncodedName = 'masaz-relaksacyjny-60min';

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $serviceEncodedName])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Serwis nie został znaleziony.');

        $this->bookingService->getServiceAndOffer($serviceEncodedName, $offerEncodedName);
    }

    public function testGetServiceAndOfferWhenOfferNotFound()
    {
        $serviceEncodedName = 'masaz-relaksacyjny';
        $offerEncodedName = 'nieistniejaca-oferta';
        
        $service = $this->createMock(Service::class);

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $serviceEncodedName])
            ->willReturn($service);

        $this->offerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'encodedName' => $offerEncodedName,
                'service' => $service
            ])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Usługa nie została znaleziona.');

        $this->bookingService->getServiceAndOffer($serviceEncodedName, $offerEncodedName);
    }

    public function testCreateBooking()
    {
        $user = $this->createMock(User::class);
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);

        $booking = $this->bookingService->createBooking($user, $service, $offer);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertSame($user, $booking->getUser());
        $this->assertSame($service, $booking->getService());
        $this->assertSame($offer, $booking->getOffer());
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

        $result = $this->bookingService->saveBooking($booking);

        $this->assertSame($booking, $result);
    }

    public function testGetUserBookingSuccess()
    {
        $bookingId = 1;
        $user = $this->createMock(User::class);
        $booking = $this->createMock(Booking::class);
        
        $booking->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $result = $this->bookingService->getUserBooking($bookingId, $user);

        $this->assertSame($booking, $result);
    }

    public function testGetUserBookingWhenBookingNotFound()
    {
        $bookingId = 999;
        $user = $this->createMock(User::class);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Wizyta nie została znaleziona.');

        $this->bookingService->getUserBooking($bookingId, $user);
    }

    public function testGetUserBookingWhenUserNotOwner()
    {
        $bookingId = 1;
        $currentUser = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);
        $booking = $this->createMock(Booking::class);
        
        $booking->expects($this->once())
            ->method('getUser')
            ->willReturn($otherUser);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Nie masz uprawnień do edycji tej wizyty.');

        $this->bookingService->getUserBooking($bookingId, $currentUser);
    }

    public function testDeleteBookingSuccess()
    {
        $bookingId = 1;
        $booking = $this->createMock(Booking::class);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->bookingRepository
            ->expects($this->once())
            ->method('remove')
            ->with($booking, true);

        $this->bookingService->deleteBooking($bookingId);
    }

    public function testDeleteBookingWhenBookingNotFound()
    {
        $bookingId = 999;

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono wizyty');

        $this->bookingService->deleteBooking($bookingId);
    }

    public function testGetAvailableSlots()
    {
        $serviceEncodedName = 'masaz-relaksacyjny';
        $offerEncodedName = 'masaz-relaksacyjny-60min';
        $employeeId = '1';
        $date = '2023-06-01';
        
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employee = $this->createMock(Employee::class);
        
        $expectedSlots = [
            new \DateTime('2023-06-01 10:00:00'),
            new \DateTime('2023-06-01 11:00:00'),
            new \DateTime('2023-06-01 12:00:00')
        ];

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $serviceEncodedName])
            ->willReturn($service);

        $this->offerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'encodedName' => $offerEncodedName,
                'service' => $service
            ])
            ->willReturn($offer);

        $this->employeeRepository
            ->expects($this->once())
            ->method('find')
            ->with($employeeId)
            ->willReturn($employee);

        $this->availabilityService
            ->expects($this->once())
            ->method('getAvailableSlots')
            ->with(
                $this->identicalTo($service),
                $this->identicalTo($offer),
                $this->identicalTo($employee),
                $this->callback(function($dateObj) use ($date) {
                    return $dateObj->format('Y-m-d') === $date;
                })
            )
            ->willReturn($expectedSlots);

        $result = $this->bookingService->getAvailableSlots($serviceEncodedName, $offerEncodedName, $employeeId, $date);

        $this->assertSame($expectedSlots, $result);
    }

    public function testGetAvailableSlotsWhenEmployeeNotFound()
    {
        $serviceEncodedName = 'masaz-relaksacyjny';
        $offerEncodedName = 'masaz-relaksacyjny-60min';
        $employeeId = '999';
        $date = '2023-06-01';
        
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $serviceEncodedName])
            ->willReturn($service);

        $this->offerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'encodedName' => $offerEncodedName,
                'service' => $service
            ])
            ->willReturn($offer);

        $this->employeeRepository
            ->expects($this->once())
            ->method('find')
            ->with($employeeId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono pracownika.');

        $this->bookingService->getAvailableSlots($serviceEncodedName, $offerEncodedName, $employeeId, $date);
    }
} 