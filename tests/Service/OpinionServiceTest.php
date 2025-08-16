<?php

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Entity\Opinion;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\OpinionRepository;
use App\Repository\ServiceRepository;
use App\Service\OpinionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class OpinionServiceTest extends TestCase
{
    private $serviceRepository;
    private $opinionRepository;
    private $bookingRepository;
    private $entityManager;
    private $csrfTokenManager;
    private $opinionService;

    protected function setUp(): void
    {
        $this->serviceRepository = $this->createMock(ServiceRepository::class);
        $this->opinionRepository = $this->createMock(OpinionRepository::class);
        $this->bookingRepository = $this->createMock(BookingRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->opinionService = new OpinionService(
            $this->serviceRepository,
            $this->opinionRepository,
            $this->bookingRepository,
            $this->entityManager,
            $this->csrfTokenManager
        );
    }

    public function testGetServiceOpinionsWithDefaultParams()
    {
        // Przygotowanie danych
        $encodedName = 'masaz-relaksacyjny';
        $service = $this->createMock(Service::class);
        $service->method('getId')->willReturn(1);

        // Parametry domyślne
        $params = [];

        // Oczekiwane zapytanie
        $expectedParams = [
            'page' => 1,
            'pageSize' => 10,
            'sorts' => '-createdAt'
        ];

        // Konfiguracja mocka repozytorium serwisu
        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $encodedName])
            ->willReturn($service);

        // Opinie
        $opinions = [
            'items' => [
                ['id' => 1, 'content' => 'Świetna usługa!', 'rating' => 5],
                ['id' => 2, 'content' => 'Bardzo profesjonalnie', 'rating' => 4]
            ],
            'total' => 2,
            'totalPages' => 1
        ];

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('getServiceOpinions')
            ->with(
                $this->identicalTo($service),
                $this->equalTo($expectedParams['page']),
                $this->equalTo($expectedParams['pageSize']),
                $this->equalTo($expectedParams['sorts'])
            )
            ->willReturn($opinions);

        // Wywołanie metody serwisu
        $result = $this->opinionService->getServiceOpinions($encodedName, $params);

        // Weryfikacja wyników
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('pageSize', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['items']);
        $this->assertEquals(1, $result['totalPages']);
        $this->assertEquals(1, $result['currentPage']);
        $this->assertEquals(10, $result['pageSize']);
        $this->assertEquals(2, $result['total']);
    }

    public function testGetServiceOpinionsWithCustomParams()
    {
        // Przygotowanie danych
        $encodedName = 'masaz-relaksacyjny';
        $service = $this->createMock(Service::class);
        $service->method('getId')->willReturn(1);

        // Parametry niestandardowe
        $params = [
            'page' => 2,
            'pageSize' => 5,
            'sorts' => 'rating'
        ];

        // Konfiguracja mocka repozytorium serwisu
        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $encodedName])
            ->willReturn($service);

        // Opinie
        $opinions = [
            'items' => [
                ['id' => 3, 'content' => 'Dobra usługa', 'rating' => 3],
                ['id' => 4, 'content' => 'Polecam', 'rating' => 4]
            ],
            'total' => 7,
            'totalPages' => 2
        ];

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('getServiceOpinions')
            ->with(
                $this->identicalTo($service),
                $this->equalTo($params['page']),
                $this->equalTo($params['pageSize']),
                $this->equalTo($params['sorts'])
            )
            ->willReturn($opinions);

        // Wywołanie metody serwisu
        $result = $this->opinionService->getServiceOpinions($encodedName, $params);

        // Weryfikacja wyników
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('pageSize', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['currentPage']);
        $this->assertEquals(5, $result['pageSize']);
        $this->assertEquals(2, $result['totalPages']);
        $this->assertEquals(7, $result['total']);
    }

    public function testGetServiceOpinionsWithServiceNotFound()
    {
        // Przygotowanie danych
        $encodedName = 'nieistniejacy-serwis';
        $params = [];

        // Konfiguracja mocka repozytorium serwisu
        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $encodedName])
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Service not found');

        // Wywołanie metody serwisu
        $this->opinionService->getServiceOpinions($encodedName, $params);
    }

    public function testGetBookingForOpinionWhenBookingNotFound()
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
        $this->expectExceptionMessage('Nie znaleziono rezerwacji.');

        // Wywołanie metody serwisu
        $this->opinionService->getBookingForOpinion($bookingId);
    }

    public function testGetBookingForOpinionWhenVisitNotEnded()
    {
        // Przygotowanie danych
        $bookingId = 1;
        $booking = $this->createMock(Booking::class);
        $booking->method('getEndTime')->willReturn(new \DateTime('+1 day'));

        // Konfiguracja mocka repozytorium rezerwacji
        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        // Oczekiwanie na wyjątek
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Nie można dodać opinii dla niezakończonej wizyty.');

        // Wywołanie metody serwisu
        $this->opinionService->getBookingForOpinion($bookingId);
    }

    public function testGetBookingForOpinionWhenOpinionAlreadyExists()
    {
        // Przygotowanie danych
        $bookingId = 1;
        $booking = $this->createMock(Booking::class);
        $booking->method('getEndTime')->willReturn(new \DateTime('-1 day'));
        $opinion = $this->createMock(Opinion::class);
        $booking->method('getOpinion')->willReturn($opinion);

        // Konfiguracja mocka repozytorium rezerwacji
        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        // Oczekiwanie na wyjątek
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Opinia dla tej wizyty już istnieje.');

        // Wywołanie metody serwisu
        $this->opinionService->getBookingForOpinion($bookingId);
    }

    public function testGetBookingForOpinionSuccess()
    {
        // Przygotowanie danych
        $bookingId = 1;
        $booking = $this->createMock(Booking::class);
        $booking->method('getEndTime')->willReturn(new \DateTime('-1 day'));
        $booking->method('getOpinion')->willReturn(null);

        // Konfiguracja mocka repozytorium rezerwacji
        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        // Wywołanie metody serwisu
        $result = $this->opinionService->getBookingForOpinion($bookingId);

        // Weryfikacja wyników
        $this->assertSame($booking, $result);
    }

    public function testCreateOpinion()
    {
        // Przygotowanie danych
        $booking = $this->createMock(Booking::class);
        $service = $this->createMock(Service::class);
        $booking->method('getService')->willReturn($service);
        
        $user = $this->createMock(User::class);

        // Wywołanie metody serwisu
        $opinion = $this->opinionService->createOpinion($booking, $user);

        // Weryfikacja wyników
        $this->assertInstanceOf(Opinion::class, $opinion);
        $this->assertSame($booking, $opinion->getBooking());
        $this->assertSame($service, $opinion->getService());
    }

    public function testSaveOpinion()
    {
        // Przygotowanie danych
        $opinion = $this->createMock(Opinion::class);

        // Konfiguracja mocka entity managera
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($opinion));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Wywołanie metody serwisu
        $this->opinionService->saveOpinion($opinion);
    }

    public function testGetOpinionForEditWhenOpinionNotFound()
    {
        // Przygotowanie danych
        $opinionId = 999;
        $user = $this->createMock(User::class);

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono opinii.');

        // Wywołanie metody serwisu
        $this->opinionService->getOpinionForEdit($opinionId, $user);
    }

    public function testGetOpinionForEditWhenUserNotOwner()
    {
        // Przygotowanie danych
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $booking = $this->createMock(Booking::class);
        $opinion->method('getBooking')->willReturn($booking);
        
        $bookingUser = $this->createMock(User::class);
        $booking->method('getUser')->willReturn($bookingUser);
        
        $currentUser = $this->createMock(User::class);

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        // Różni użytkownicy
        $booking->method('getUser')->willReturn($bookingUser);
        
        // Oczekiwanie na wyjątek
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Nie masz uprawnień do edycji tej opinii.');

        // Wywołanie metody serwisu
        $this->opinionService->getOpinionForEdit($opinionId, $currentUser);
    }

    public function testGetOpinionForEditSuccess()
    {
        // Przygotowanie danych
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $booking = $this->createMock(Booking::class);
        $opinion->method('getBooking')->willReturn($booking);
        
        $user = $this->createMock(User::class);
        $booking->method('getUser')->willReturn($user);

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        // Wywołanie metody serwisu
        $result = $this->opinionService->getOpinionForEdit($opinionId, $user);

        // Weryfikacja wyników
        $this->assertSame($opinion, $result);
    }

    public function testDeleteOpinionWhenOpinionNotFound()
    {
        // Przygotowanie danych
        $opinionId = 999;
        $user = $this->createMock(User::class);
        $csrfToken = 'valid-token';

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn(null);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono opinii.');

        // Wywołanie metody serwisu
        $this->opinionService->deleteOpinion($opinionId, $user, $csrfToken);
    }

    public function testDeleteOpinionWhenInvalidCsrfToken()
    {
        // Przygotowanie danych
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $opinion->method('getId')->willReturn($opinionId);
        
        $user = $this->createMock(User::class);
        $invalidToken = 'invalid-token';

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        // Konfiguracja mocka CSRF token managera
        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(function ($token) use ($invalidToken, $opinionId) {
                return $token instanceof CsrfToken &&
                    $token->getValue() === $invalidToken &&
                    $token->getId() === 'delete-opinion-' . $opinionId;
            }))
            ->willReturn(false);

        // Oczekiwanie na wyjątek
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Nieprawidłowy token CSRF.');

        // Wywołanie metody serwisu
        $this->opinionService->deleteOpinion($opinionId, $user, $invalidToken);
    }

    public function testDeleteOpinionWhenUserNotOwner()
    {
        // Przygotowanie danych
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $booking = $this->createMock(Booking::class);
        $opinion->method('getBooking')->willReturn($booking);
        
        $bookingUser = $this->createMock(User::class);
        $booking->method('getUser')->willReturn($bookingUser);
        
        $currentUser = $this->createMock(User::class);
        
        $validToken = 'valid-token';

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        // Konfiguracja mocka CSRF token managera
        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true);

        // Oczekiwanie na wyjątek
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Nie masz uprawnień do usunięcia tej opinii.');

        // Wywołanie metody serwisu
        $this->opinionService->deleteOpinion($opinionId, $currentUser, $validToken);
    }

    public function testDeleteOpinionSuccess()
    {
        // Przygotowanie danych
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $booking = $this->createMock(Booking::class);
        $opinion->method('getBooking')->willReturn($booking);
        
        $user = $this->createMock(User::class);
        $booking->method('getUser')->willReturn($user);
        
        $validToken = 'valid-token';

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        // Konfiguracja mocka CSRF token managera
        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true);

        // Konfiguracja mocka repozytorium opinii
        $this->opinionRepository
            ->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($opinion), true);

        // Wywołanie metody serwisu
        $this->opinionService->deleteOpinion($opinionId, $user, $validToken);
    }
} 