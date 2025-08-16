<?php

namespace App\Tests\Controller;

use App\Controller\BookingController;
use App\Entity\Booking;
use App\Entity\Employee;
use App\Entity\Offer;
use App\Entity\Service;
use App\Entity\User;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\EmployeeRepository;
use App\Repository\OfferRepository;
use App\Repository\ServiceRepository;
use App\Service\AvailabilityService;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BookingControllerTest extends TestCase
{
    private $security;
    private $bookingRepository;
    private $employeeRepository;
    private $offerRepository;
    private $serviceRepository;
    private $availabilityService;
    private $entityManager;
    private $bookingService;
    private $bookingController;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->bookingRepository = $this->createMock(BookingRepository::class);
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->offerRepository = $this->createMock(OfferRepository::class);
        $this->serviceRepository = $this->createMock(ServiceRepository::class);
        $this->availabilityService = $this->createMock(AvailabilityService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bookingService = $this->createMock(BookingService::class);

        $this->bookingController = $this->getMockBuilder(BookingController::class)
            ->setConstructorArgs([
                $this->security,
                $this->bookingRepository,
                $this->employeeRepository,
                $this->offerRepository,
                $this->serviceRepository,
                $this->availabilityService,
                $this->entityManager,
                $this->bookingService
            ])
            ->onlyMethods(['render', 'redirectToRoute', 'createForm', 'addFlash', 'json'])
            ->getMock();
    }

    public function testIndexWhenUserNotLoggedIn()
    {
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied');

        $this->bookingController->index();
    }

    public function testIndexForRegularUser()
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $bookings = [
            $this->createMock(Booking::class),
            $this->createMock(Booking::class)
        ];

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->employeeRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn(null);

        $this->bookingRepository
            ->expects($this->once())
            ->method('findByUser')
            ->with(1)
            ->willReturn($bookings);

        $this->bookingController
            ->expects($this->once())
            ->method('render')
            ->with(
                'booking/index.html.twig',
                $this->callback(function ($params) use ($bookings) {
                    return $params['bookings'] === $bookings &&
                           $params['currentTime'] instanceof \DateTimeImmutable;
                })
            )
            ->willReturn(new Response());

        $response = $this->bookingController->index();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testIndexForEmployee()
    {
        $user = $this->createMock(User::class);
        $employee = $this->createMock(Employee::class);
        $employee->method('getId')->willReturn(2);
        $bookings = [
            $this->createMock(Booking::class),
            $this->createMock(Booking::class)
        ];

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->employeeRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($employee);

        $this->bookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('employee_bookings')
            ->willReturn(new RedirectResponse('/wizyty/pracownik'));

        $response = $this->bookingController->index();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateWhenUserNotLoggedIn()
    {
        $serviceEncodedName = 'relaxing-massage';
        $offerEncodedName = 'relaxing-massage-60min';
        $request = $this->createMock(Request::class);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->bookingController
            ->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'You must be logged in to book a visit.');

        $this->bookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_login')
            ->willReturn(new RedirectResponse('/login'));

        $response = $this->bookingController->create($request, $serviceEncodedName, $offerEncodedName);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testCreateSuccess()
    {
        $serviceEncodedName = 'relaxing-massage';
        $offerEncodedName = 'relaxing-massage-60min';
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $booking = $this->createMock(Booking::class);
        $employees = [$this->createMock(Employee::class)];
        $formView = new FormView();

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingService
            ->expects($this->once())
            ->method('getServiceAndOffer')
            ->with($serviceEncodedName, $offerEncodedName)
            ->willReturn(['service' => $service, 'offer' => $offer]);

        $this->bookingService
            ->expects($this->once())
            ->method('createBooking')
            ->with($user, $service, $offer)
            ->willReturn($booking);

        $this->employeeRepository
            ->expects($this->once())
            ->method('findByServiceEncodedName')
            ->with($serviceEncodedName)
            ->willReturn($employees);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        $this->bookingController
            ->expects($this->once())
            ->method('createForm')
            ->with(
                BookingType::class,
                $booking,
                $this->callback(function ($options) use ($service, $offer, $employees) {
                    return $options['service'] === $service &&
                           $options['offer'] === $offer &&
                           $options['employees'] === $employees;
                })
            )
            ->willReturn($form);

        $this->bookingController
            ->expects($this->once())
            ->method('render')
            ->with(
                'booking/create.html.twig',
                $this->callback(function ($params) use ($formView, $service, $offer) {
                    return $params['form'] === $formView &&
                           $params['service'] === $service &&
                           $params['offer'] === $offer;
                })
            )
            ->willReturn(new Response());

        $response = $this->bookingController->create($request, $serviceEncodedName, $offerEncodedName);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateWithFormSubmitted()
    {
        $serviceEncodedName = 'relaxing-massage';
        $offerEncodedName = 'relaxing-massage-60min';
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $booking = $this->createMock(Booking::class);
        $employees = [$this->createMock(Employee::class)];

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingService
            ->expects($this->once())
            ->method('getServiceAndOffer')
            ->with($serviceEncodedName, $offerEncodedName)
            ->willReturn(['service' => $service, 'offer' => $offer]);

        $this->bookingService
            ->expects($this->once())
            ->method('createBooking')
            ->with($user, $service, $offer)
            ->willReturn($booking);

        $this->bookingService
            ->expects($this->once())
            ->method('saveBooking')
            ->with($booking)
            ->willReturn($booking);

        $this->employeeRepository
            ->expects($this->once())
            ->method('findByServiceEncodedName')
            ->with($serviceEncodedName)
            ->willReturn($employees);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $this->bookingController
            ->expects($this->once())
            ->method('createForm')
            ->with(
                BookingType::class,
                $booking,
                $this->callback(function ($options) use ($service, $offer, $employees) {
                    return $options['service'] === $service &&
                           $options['offer'] === $offer &&
                           $options['employees'] === $employees;
                })
            )
            ->willReturn($form);

        $this->bookingController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', 'The visit has been successfully booked.');

        $this->bookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('booking_index')
            ->willReturn(new RedirectResponse('/bookings'));

        $response = $this->bookingController->create($request, $serviceEncodedName, $offerEncodedName);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testCreateWithException()
    {
        $serviceEncodedName = 'relaxing-massage';
        $offerEncodedName = 'relaxing-massage-60min';
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $exceptionMessage = 'Service not found.';
        $exception = new \Exception($exceptionMessage, 404);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingService
            ->expects($this->once())
            ->method('getServiceAndOffer')
            ->with($serviceEncodedName, $offerEncodedName)
            ->willThrowException($exception);

        $this->bookingController
            ->expects($this->once())
            ->method('addFlash')
            ->with('danger', $exceptionMessage);

        $this->bookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('home')
            ->willReturn(new RedirectResponse('/'));

        $response = $this->bookingController->create($request, $serviceEncodedName, $offerEncodedName);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testGetAvailableSlotsSuccess()
    {
        $serviceEncodedName = 'relaxing-massage';
        $offerEncodedName = 'relaxing-massage-60min';
        $employeeId = '1';
        $date = '2023-06-01';
        
        $slots = [
            new \DateTime('2023-06-01 10:00:00'),
            new \DateTime('2023-06-01 11:00:00'),
            new \DateTime('2023-06-01 12:00:00')
        ];
        
        $expectedResponse = [
            'slots' => [
                ['time' => '10:00', 'datetime' => '2023-06-01T10:00:00'],
                ['time' => '11:00', 'datetime' => '2023-06-01T11:00:00'],
                ['time' => '12:00', 'datetime' => '2023-06-01T12:00:00']
            ]
        ];

        $request = $this->createMock(Request::class);
        $request->query = new InputBag([
            'employee' => $employeeId,
            'date' => $date
        ]);

        $this->bookingService
            ->expects($this->once())
            ->method('getAvailableSlots')
            ->with($serviceEncodedName, $offerEncodedName, $employeeId, $date)
            ->willReturn($slots);

        $this->bookingController
            ->expects($this->once())
            ->method('json')
            ->with($this->callback(function($data) {
                return isset($data['slots']) && count($data['slots']) === 3;
            }))
            ->willReturn(new JsonResponse($expectedResponse));

        $response = $this->bookingController->getAvailableSlots($request, $serviceEncodedName, $offerEncodedName);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testGetAvailableSlotsWithMissingParameters()
    {
        $serviceEncodedName = 'relaxing-massage';
        $offerEncodedName = 'relaxing-massage-60min';
        
        $expectedResponse = [
            'error' => 'Missing query parameters (employee, date).'
        ];

        $request = $this->createMock(Request::class);
        $request->query = new InputBag([]);

        $this->bookingController
            ->expects($this->once())
            ->method('json')
            ->with(
                $this->equalTo(['error' => 'Missing query parameters (employee, date).']),
                $this->equalTo(Response::HTTP_BAD_REQUEST)
            )
            ->willReturn(new JsonResponse($expectedResponse, Response::HTTP_BAD_REQUEST));

        $response = $this->bookingController->getAvailableSlots($request, $serviceEncodedName, $offerEncodedName);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testGetAvailableSlotsWithException()
    {
        $serviceEncodedName = 'relaxing-massage';
        $offerEncodedName = 'relaxing-massage-60min';
        $employeeId = '1';
        $date = '2023-06-01';
        $errorMessage = 'Employee not found.';
        
        $expectedResponse = [
            'error' => $errorMessage
        ];

        $request = $this->createMock(Request::class);
        $request->query = new InputBag([
            'employee' => $employeeId,
            'date' => $date
        ]);

        $this->bookingService
            ->expects($this->once())
            ->method('getAvailableSlots')
            ->with($serviceEncodedName, $offerEncodedName, $employeeId, $date)
            ->willThrowException(new \Exception($errorMessage, 404));

        $this->bookingController
            ->expects($this->once())
            ->method('json')
            ->with(
                $this->equalTo(['error' => $errorMessage]),
                $this->equalTo(Response::HTTP_NOT_FOUND)
            )
            ->willReturn(new JsonResponse($expectedResponse, Response::HTTP_NOT_FOUND));

        $response = $this->bookingController->getAvailableSlots($request, $serviceEncodedName, $offerEncodedName);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testEditWhenUserNotLoggedIn()
    {
        $bookingId = 1;
        $request = $this->createMock(Request::class);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->bookingController
            ->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'You must be logged in to edit a visit.');

        $this->bookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_login')
            ->willReturn(new RedirectResponse('/login'));

        $response = $this->bookingController->edit($request, $bookingId);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testEditSuccess()
    {
        $bookingId = 1;
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $booking = $this->createMock(Booking::class);
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employees = [$this->createMock(Employee::class)];
        $formView = new FormView();

        $booking->method('getService')->willReturn($service);
        $booking->method('getOffer')->willReturn($offer);

        $service->method('getEncodedName')->willReturn('relaxing-massage');

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingService
            ->expects($this->once())
            ->method('getUserBooking')
            ->with($bookingId, $user)
            ->willReturn($booking);

        $this->employeeRepository
            ->expects($this->once())
            ->method('findByServiceEncodedName')
            ->with('relaxing-massage')
            ->willReturn($employees);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        $this->bookingController
            ->expects($this->once())
            ->method('createForm')
            ->with(
                BookingType::class,
                $booking,
                $this->callback(function ($options) use ($service, $offer, $employees) {
                    return $options['service'] === $service &&
                           $options['offer'] === $offer &&
                           $options['employees'] === $employees;
                })
            )
            ->willReturn($form);

        $this->bookingController
            ->expects($this->once())
            ->method('render')
            ->with(
                'booking/edit.html.twig',
                $this->callback(function ($params) use ($formView, $booking, $service, $offer) {
                    return $params['form'] === $formView &&
                           $params['booking'] === $booking &&
                           $params['service'] === $service &&
                           $params['offer'] === $offer;
                })
            )
            ->willReturn(new Response());

        $response = $this->bookingController->edit($request, $bookingId);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditWithFormSubmitted()
    {
        $bookingId = 1;
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $booking = $this->createMock(Booking::class);
        $service = $this->createMock(Service::class);
        $offer = $this->createMock(Offer::class);
        $employees = [$this->createMock(Employee::class)];

        $booking->method('getService')->willReturn($service);
        $booking->method('getOffer')->willReturn($offer);

        $service->method('getEncodedName')->willReturn('relaxing-massage');

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingService
            ->expects($this->once())
            ->method('getUserBooking')
            ->with($bookingId, $user)
            ->willReturn($booking);

        $this->bookingService
            ->expects($this->once())
            ->method('saveBooking')
            ->with($booking)
            ->willReturn($booking);

        $this->employeeRepository
            ->expects($this->once())
            ->method('findByServiceEncodedName')
            ->with('relaxing-massage')
            ->willReturn($employees);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $this->bookingController
            ->expects($this->once())
            ->method('createForm')
            ->with(
                BookingType::class,
                $booking,
                $this->callback(function ($options) use ($service, $offer, $employees) {
                    return $options['service'] === $service &&
                           $options['offer'] === $offer &&
                           $options['employees'] === $employees;
                })
            )
            ->willReturn($form);

        $this->bookingController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', 'The visit has been successfully updated.');

        $this->bookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('booking_index')
            ->willReturn(new RedirectResponse('/bookings'));

        $response = $this->bookingController->edit($request, $bookingId);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testEditWithException()
    {
        $bookingId = 1;
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $errorMessage = 'Visit not found.';

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->bookingService
            ->expects($this->once())
            ->method('getUserBooking')
            ->with($bookingId, $user)
            ->willThrowException(new \Exception($errorMessage));

        $this->bookingController
            ->expects($this->once())
            ->method('addFlash')
            ->with('danger', $errorMessage);

        $this->bookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('booking_index')
            ->willReturn(new RedirectResponse('/bookings'));

        $response = $this->bookingController->edit($request, $bookingId);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteSuccess()
    {
        $bookingId = 1;
        $expectedResponse = [
            'success' => true,
            'message' => 'Visit has been cancelled'
        ];

        $this->bookingService
            ->expects($this->once())
            ->method('deleteBooking')
            ->with($bookingId);

        
        $response = $this->bookingController->delete($bookingId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(json_encode($expectedResponse), $response->getContent());
    }

    public function testDeleteWithException()
    {
        $bookingId = 999;
        $errorMessage = 'Visit not found';
        $expectedResponse = [
            'success' => false,
            'message' => $errorMessage
        ];

        $this->bookingService
            ->expects($this->once())
            ->method('deleteBooking')
            ->with($bookingId)
            ->willThrowException(new \Exception($errorMessage, 404));

        $this->bookingController
            ->expects($this->once())
            ->method('json')
            ->with(
                $this->callback(function ($params) use ($errorMessage) {
                    return $params['success'] === false &&
                           $params['message'] === $errorMessage;
                }),
                $this->equalTo(Response::HTTP_NOT_FOUND)
            )
            ->willReturn(new JsonResponse($expectedResponse, Response::HTTP_NOT_FOUND));

        $response = $this->bookingController->delete($bookingId);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
