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

        // Konfiguracja mocka repozytorium serwisu
        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $serviceEncodedName])
            ->willReturn($service);

        // Konfiguracja mocka repozytorium oferty
        $this->offerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'encodedName' => $offerEncodedName,
                'service' => $service
            ])
            ->willReturn($offer);

        // Wywołanie metody serwisu
        $result = $this->bookingService->getServiceAndOffer($serviceEncodedName, $offerEncodedName);

        // Weryfikacja wyników
        $this->assertIsArray($result);
        $this->assertArrayHasKey('service', $result);
        $this->assertArrayHasKey('offer', $result);
        $this->assertSame($service, $result['service']);
        $this->assertSame($offer, $result['offer']);
    }

    public function testGetServiceAndOfferWhenServiceNotFound()
    {
        // Przygotowanie danych
        $serviceEncodedName = 'nieistniejacy-serwis';
        $offerEncodedName = 'masaz-relaksacyjny-60min';

        // Konfiguracja mocka repozytorium serwisu
        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $serviceEncodedName])
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Serwis nie został znaleziony.');

        // Wywołanie metody serwisu
        $this->bookingService->getServiceAndOffer($serviceEncodedName, $offerEncodedName);
    }

    public function testGetServiceAndOfferWhenOfferNotFound()
    {
        // Przygotowanie danych
        $serviceEncodedName = 'masaz-relaksacyjny';
        $offerEncodedName = 'nieistniejaca-oferta';
        
        $service = $this->createMock(Service::class);

        // Konfiguracja mocka repozytorium serwisu
        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $serviceEncodedName])
            ->willReturn($service);

        // Konfiguracja mocka repozytorium oferty
        $this->offerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'encodedName' => $offerEncodedName,
                'service' => $service
            ])
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Usługa nie została znaleziona.');

        // Wywołanie metody serwisu
        $this->bookingService->getServiceAndOffer($serviceEncodedName, $offerEncodedName);
    }

    public function testCreateBooking()
    {
        // Przygotowanie danych
        $user = $this->createMock(User::class);
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);

        // Wywołanie metody serwisu
        $booking = $this->bookingService->createBooking($user, $service, $offer);

        // Weryfikacja wyników
        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertSame($user, $booking->getUser());
        $this->assertSame($service, $booking->getService());
        $this->assertSame($offer, $booking->getOffer());
    }

    public function testSaveBooking()
    {
        // Przygotowanie danych
        $booking = $this->createMock(Booking::class);
        
        // Konfiguracja mocka booking
        $booking->expects($this->once())
            ->method('setEndTime')
            ->willReturn($booking);

        // Konfiguracja mocka repozytorium rezerwacji
        $this->bookingRepository
            ->expects($this->once())
            ->method('save')
            ->with($booking, true);

        // Wywołanie metody serwisu
        $result = $this->bookingService->saveBooking($booking);

        // Weryfikacja wyników
        $this->assertSame($booking, $result);
    }

    public function testGetUserBookingSuccess()
    {
        // Przygotowanie danych
        $bookingId = 1;
        $user = $this->createMock(User::class);
        $booking = $this->createMock(Booking::class);
        
        // Konfiguracja mocka booking
        $booking->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // Konfiguracja mocka repozytorium rezerwacji
        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        // Wywołanie metody serwisu
        $result = $this->bookingService->getUserBooking($bookingId, $user);

        // Weryfikacja wyników
        $this->assertSame($booking, $result);
    }

    public function testGetUserBookingWhenBookingNotFound()
    {
        // Przygotowanie danych
        $bookingId = 999;
        $user = $this->createMock(User::class);

        // Konfiguracja mocka repozytorium rezerwacji
        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Wizyta nie została znaleziona.');

        // Wywołanie metody serwisu
        $this->bookingService->getUserBooking($bookingId, $user);
    }

    public function testGetUserBookingWhenUserNotOwner()
    {
        // Przygotowanie danych
        $bookingId = 1;
        $currentUser = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);
        $booking = $this->createMock(Booking::class);
        
        // Konfiguracja mocka booking
        $booking->expects($this->once())
            ->method('getUser')
            ->willReturn($otherUser);

        // Konfiguracja mocka repozytorium rezerwacji
        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        // Oczekiwanie na wyjątek
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Nie masz uprawnień do edycji tej wizyty.');

        // Wywołanie metody serwisu
        $this->bookingService->getUserBooking($bookingId, $currentUser);
    }

    public function testDeleteBookingSuccess()
    {
        // Przygotowanie danych
        $bookingId = 1;
        $booking = $this->createMock(Booking::class);

        // Konfiguracja mocka repozytorium rezerwacji
        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        // Konfiguracja mocka repozytorium rezerwacji dla usunięcia
        $this->bookingRepository
            ->expects($this->once())
            ->method('remove')
            ->with($booking, true);

        // Wywołanie metody serwisu
        $this->bookingService->deleteBooking($bookingId);
    }

    public function testDeleteBookingWhenBookingNotFound()
    {
        // Przygotowanie danych
        $bookingId = 999;

        // Konfiguracja mocka repozytorium rezerwacji
        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono wizyty');

        // Wywołanie metody serwisu
        $this->bookingService->deleteBooking($bookingId);
    }

    public function testGetAvailableSlots()
    {
        // Przygotowanie danych
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

        // Konfiguracja mocka repozytorium serwisu i oferty
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

        // Konfiguracja mocka repozytorium pracownika
        $this->employeeRepository
            ->expects($this->once())
            ->method('find')
            ->with($employeeId)
            ->willReturn($employee);

        // Konfiguracja mocka serwisu dostępności
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

        // Wywołanie metody serwisu
        $result = $this->bookingService->getAvailableSlots($serviceEncodedName, $offerEncodedName, $employeeId, $date);

        // Weryfikacja wyników
        $this->assertSame($expectedSlots, $result);
    }

    public function testGetAvailableSlotsWhenEmployeeNotFound()
    {
        // Przygotowanie danych
        $serviceEncodedName = 'masaz-relaksacyjny';
        $offerEncodedName = 'masaz-relaksacyjny-60min';
        $employeeId = '999';
        $date = '2023-06-01';
        
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);

        // Konfiguracja mocka repozytorium serwisu i oferty
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

        // Konfiguracja mocka repozytorium pracownika
        $this->employeeRepository
            ->expects($this->once())
            ->method('find')
            ->with($employeeId)
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono pracownika.');

        // Wywołanie metody serwisu
        $this->bookingService->getAvailableSlots($serviceEncodedName, $offerEncodedName, $employeeId, $date);
    }
} 