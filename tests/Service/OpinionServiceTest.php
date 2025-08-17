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
        $encodedName = 'masaz-relaksacyjny';
        $service = $this->createMock(Service::class);
        $service->method('getId')->willReturn(1);

        $params = [];

        $expectedParams = [
            'page' => 1,
            'pageSize' => 10,
            'sorts' => '-createdAt'
        ];

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $encodedName])
            ->willReturn($service);

        $opinions = [
            'items' => [
                ['id' => 1, 'content' => 'Świetna usługa!', 'rating' => 5],
                ['id' => 2, 'content' => 'Bardzo profesjonalnie', 'rating' => 4]
            ],
            'total' => 2,
            'totalPages' => 1
        ];

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

        $result = $this->opinionService->getServiceOpinions($encodedName, $params);

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
        $encodedName = 'masaz-relaksacyjny';
        $service = $this->createMock(Service::class);
        $service->method('getId')->willReturn(1);

        $params = [
            'page' => 2,
            'pageSize' => 5,
            'sorts' => 'rating'
        ];

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $encodedName])
            ->willReturn($service);

        $opinions = [
            'items' => [
                ['id' => 3, 'content' => 'Dobra usługa', 'rating' => 3],
                ['id' => 4, 'content' => 'Polecam', 'rating' => 4]
            ],
            'total' => 7,
            'totalPages' => 2
        ];

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

        $result = $this->opinionService->getServiceOpinions($encodedName, $params);

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
        $encodedName = 'nieistniejacy-serwis';
        $params = [];

        $this->serviceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['encodedName' => $encodedName])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Serwis nie został znaleziony');

        $this->opinionService->getServiceOpinions($encodedName, $params);
    }

    public function testGetBookingForOpinionWhenBookingNotFound()
    {
        $bookingId = 999;

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Rezerwacja nie została znaleziona.');

        $this->opinionService->getBookingForOpinion($bookingId);
    }

    public function testGetBookingForOpinionWhenVisitNotEnded()
    {
        $bookingId = 1;
        $booking = $this->createMock(Booking::class);
        $booking->method('getEndTime')->willReturn(new \DateTime('+1 day'));

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Nie można dodać opinii dla niezakończonej wizyty.');

        $this->opinionService->getBookingForOpinion($bookingId);
    }

    public function testGetBookingForOpinionWhenOpinionAlreadyExists()
    {
        $bookingId = 1;
        $booking = $this->createMock(Booking::class);
        $booking->method('getEndTime')->willReturn(new \DateTime('-1 day'));
        $opinion = $this->createMock(Opinion::class);
        $booking->method('getOpinion')->willReturn($opinion);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Opinia dla tej wizyty już istnieje.');

        $this->opinionService->getBookingForOpinion($bookingId);
    }

    public function testGetBookingForOpinionSuccess()
    {
        $bookingId = 1;
        $booking = $this->createMock(Booking::class);
        $booking->method('getEndTime')->willReturn(new \DateTime('-1 day'));
        $booking->method('getOpinion')->willReturn(null);

        $this->bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $result = $this->opinionService->getBookingForOpinion($bookingId);

        $this->assertSame($booking, $result);
    }

    public function testCreateOpinion()
    {
        $booking = $this->createMock(Booking::class);
        $service = $this->createMock(Service::class);
        $booking->method('getService')->willReturn($service);
        
        $user = $this->createMock(User::class);

        $opinion = $this->opinionService->createOpinion($booking, $user);

        $this->assertInstanceOf(Opinion::class, $opinion);
        $this->assertSame($booking, $opinion->getBooking());
        $this->assertSame($service, $opinion->getService());
    }

    public function testSaveOpinion()
    {
        $opinion = $this->createMock(Opinion::class);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($opinion));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->opinionService->saveOpinion($opinion);
    }

    public function testGetOpinionForEditWhenOpinionNotFound()
    {
        $opinionId = 999;
        $user = $this->createMock(User::class);

        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Opinia nie została znaleziona.');

        $this->opinionService->getOpinionForEdit($opinionId, $user);
    }

    public function testGetOpinionForEditWhenUserNotOwner()
    {
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $booking = $this->createMock(Booking::class);
        $opinion->method('getBooking')->willReturn($booking);
        
        $bookingUser = $this->createMock(User::class);
        $booking->method('getUser')->willReturn($bookingUser);
        
        $currentUser = $this->createMock(User::class);

        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        $booking->method('getUser')->willReturn($bookingUser);
        
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Nie masz uprawnień do edycji tej opinii.');

        $this->opinionService->getOpinionForEdit($opinionId, $currentUser);
    }

    public function testGetOpinionForEditSuccess()
    {
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $booking = $this->createMock(Booking::class);
        $opinion->method('getBooking')->willReturn($booking);
        
        $user = $this->createMock(User::class);
        $booking->method('getUser')->willReturn($user);

        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        $result = $this->opinionService->getOpinionForEdit($opinionId, $user);

        $this->assertSame($opinion, $result);
    }

    public function testDeleteOpinionWhenOpinionNotFound()
    {
        $opinionId = 999;
        $user = $this->createMock(User::class);
        $csrfToken = 'valid-token';

        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Opinia nie została znaleziona.');

        $this->opinionService->deleteOpinion($opinionId, $user, $csrfToken);
    }

    public function testDeleteOpinionWhenInvalidCsrfToken()
    {
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $opinion->method('getId')->willReturn($opinionId);
        
        $user = $this->createMock(User::class);
        $invalidToken = 'invalid-token';

        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(function ($token) use ($invalidToken, $opinionId) {
                return $token instanceof CsrfToken &&
                    $token->getValue() === $invalidToken &&
                    $token->getId() === 'delete-opinion-' . $opinionId;
            }))
            ->willReturn(false);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Nieprawidłowy token CSRF.');

        $this->opinionService->deleteOpinion($opinionId, $user, $invalidToken);
    }

    public function testDeleteOpinionWhenUserNotOwner()
    {
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $booking = $this->createMock(Booking::class);
        $opinion->method('getBooking')->willReturn($booking);
        
        $bookingUser = $this->createMock(User::class);
        $booking->method('getUser')->willReturn($bookingUser);
        
        $currentUser = $this->createMock(User::class);
        
        $validToken = 'valid-token';

        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Nie masz uprawnień do usunięcia tej opinii.');

        $this->opinionService->deleteOpinion($opinionId, $currentUser, $validToken);
    }

    public function testDeleteOpinionSuccess()
    {
        $opinionId = 1;
        $opinion = $this->createMock(Opinion::class);
        $booking = $this->createMock(Booking::class);
        $opinion->method('getBooking')->willReturn($booking);
        
        $user = $this->createMock(User::class);
        $booking->method('getUser')->willReturn($user);
        
        $validToken = 'valid-token';

        $this->opinionRepository
            ->expects($this->once())
            ->method('find')
            ->with($opinionId)
            ->willReturn($opinion);

        $this->csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true);

        $this->opinionRepository
            ->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($opinion), true);

        
        $this->opinionService->deleteOpinion($opinionId, $user, $validToken);
    }
} 