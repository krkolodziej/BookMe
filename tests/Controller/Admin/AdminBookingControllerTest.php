<?php

namespace App\Tests\Controller\Admin;

use App\Constant\FlashMessages;
use App\Controller\Admin\AdminBookingController;
use App\Entity\Booking;
use App\Entity\Employee;
use App\Entity\Offer;
use App\Entity\Service;
use App\Form\AdminBookingType;
use App\Repository\BookingRepository;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use App\Service\AdminBookingService;
use App\Service\AvailabilityService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminBookingControllerTest extends TestCase
{
    private $bookingRepository;
    private $serviceRepository;
    private $employeeRepository;
    private $availabilityService;
    private $adminBookingService;
    private $adminBookingController;

    protected function setUp(): void
    {
        $this->bookingRepository = $this->createMock(BookingRepository::class);
        $this->serviceRepository = $this->createMock(ServiceRepository::class);
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->availabilityService = $this->createMock(AvailabilityService::class);
        $this->adminBookingService = $this->createMock(AdminBookingService::class);

        $this->adminBookingController = $this->getMockBuilder(AdminBookingController::class)
            ->setConstructorArgs([
                $this->bookingRepository,
                $this->serviceRepository,
                $this->employeeRepository,
                $this->availabilityService,
                $this->adminBookingService
            ])
            ->onlyMethods(['render', 'redirectToRoute', 'createForm', 'addFlash', 'json'])
            ->getMock();
    }

    public function testIndexSuccess()
    {
        $encodedName = 'relaxing-massage';
        $service = $this->createMock(Service::class);
        $bookings = [$this->createMock(Booking::class)];

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willReturn($service);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceBookings')
            ->with($encodedName)
            ->willReturn($bookings);

        $this->adminBookingController
            ->expects($this->once())
            ->method('render')
            ->with(
                'admin/booking/index.html.twig',
                [
                    'service' => $service,
                    'bookings' => $bookings
                ]
            )
            ->willReturn(new Response());

        $response = $this->adminBookingController->index($encodedName);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testIndexWithServiceNotFound()
    {
        $encodedName = 'non-existent-service';
        $exception = new NotFoundHttpException(FlashMessages::SERVICE_NOT_FOUND);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willThrowException($exception);

        $this->adminBookingController
            ->expects($this->once())
            ->method('addFlash')
            ->with('danger', FlashMessages::SERVICE_NOT_FOUND);

        $this->adminBookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_service_index')
            ->willReturn(new RedirectResponse('/admin/services'));

        $response = $this->adminBookingController->index($encodedName);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testCreateSuccess()
    {
        $encodedName = 'relaxing-massage';
        $request = $this->createMock(Request::class);
        $service = $this->createMock(Service::class);
        $booking = $this->createMock(Booking::class);
        $employees = [$this->createMock(Employee::class)];
        $formView = new FormView();

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willReturn($service);

        $this->adminBookingService
            ->expects($this->once())
            ->method('createBooking')
            ->with($service)
            ->willReturn($booking);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getEmployeesForService')
            ->with($encodedName)
            ->willReturn($employees);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        $this->adminBookingController
            ->expects($this->once())
            ->method('createForm')
            ->with(
                AdminBookingType::class,
                $booking,
                [
                    'service' => $service,
                    'employees' => $employees
                ]
            )
            ->willReturn($form);

        $this->adminBookingController
            ->expects($this->once())
            ->method('render')
            ->with(
                'admin/booking/create.html.twig',
                [
                    'form' => $formView,
                    'service' => $service
                ]
            )
            ->willReturn(new Response());

        $response = $this->adminBookingController->create($request, $encodedName);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateWithFormSubmitted()
    {
        $encodedName = 'relaxing-massage';
        $request = $this->createMock(Request::class);
        $service = $this->createMock(Service::class);
        $booking = $this->createMock(Booking::class);
        $employees = [$this->createMock(Employee::class)];

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willReturn($service);

        $this->adminBookingService
            ->expects($this->once())
            ->method('createBooking')
            ->with($service)
            ->willReturn($booking);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getEmployeesForService')
            ->with($encodedName)
            ->willReturn($employees);

        $this->adminBookingService
            ->expects($this->once())
            ->method('saveBooking')
            ->with($booking);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $this->adminBookingController
            ->expects($this->once())
            ->method('createForm')
            ->with(
                AdminBookingType::class,
                $booking,
                [
                    'service' => $service,
                    'employees' => $employees
                ]
            )
            ->willReturn($form);

        $this->adminBookingController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', FlashMessages::VISIT_CREATED_SUCCESS);

        $this->adminBookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_service_bookings', ['encodedName' => $encodedName])
            ->willReturn(new RedirectResponse('/admin/service/' . $encodedName . '/bookings'));

        $response = $this->adminBookingController->create($request, $encodedName);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testEditSuccess()
    {
        $encodedName = 'relaxing-massage';
        $bookingId = 1;
        $request = $this->createMock(Request::class);
        $service = $this->createMock(Service::class);
        $booking = $this->createMock(Booking::class);
        $employees = [$this->createMock(Employee::class)];
        $formView = new FormView();

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willReturn($service);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getBookingForEdit')
            ->with($bookingId, $service)
            ->willReturn($booking);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getEmployeesForService')
            ->with($encodedName)
            ->willReturn($employees);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        $this->adminBookingController
            ->expects($this->once())
            ->method('createForm')
            ->with(
                AdminBookingType::class,
                $booking,
                [
                    'service' => $service,
                    'employees' => $employees
                ]
            )
            ->willReturn($form);

        $this->adminBookingController
            ->expects($this->once())
            ->method('render')
            ->with(
                'admin/booking/edit.html.twig',
                [
                    'form' => $formView,
                    'booking' => $booking,
                    'service' => $service
                ]
            )
            ->willReturn(new Response());

        $response = $this->adminBookingController->edit($request, $bookingId, $encodedName);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDeleteSuccess()
    {
        $encodedName = 'relaxing-massage';
        $bookingId = 1;
        $request = $this->createMock(Request::class);
        $service = $this->createMock(Service::class);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willReturn($service);

        $this->adminBookingService
            ->expects($this->once())
            ->method('deleteBooking')
            ->with($bookingId, $service);

        $this->adminBookingController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', FlashMessages::VISIT_DELETED_SUCCESS);

        $this->adminBookingController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_service_bookings', ['encodedName' => $encodedName])
            ->willReturn(new RedirectResponse('/admin/service/' . $encodedName . '/bookings'));

        $response = $this->adminBookingController->delete($request, $bookingId, $encodedName);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testGetAvailableSlotsSuccess()
    {
        $encodedName = 'relaxing-massage';
        $offerId = '1';
        $employeeId = '2';
        $date = '2023-06-01';
        
        $service = $this->createMock(Service::class);
        
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
            'offer' => $offerId,
            'employee' => $employeeId,
            'date' => $date
        ]);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willReturn($service);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getAvailableTimeSlots')
            ->with($service, $offerId, $employeeId, $date)
            ->willReturn($slots);

        $this->adminBookingController
            ->expects($this->once())
            ->method('json')
            ->with($this->callback(function($data) {
                return isset($data['slots']) && count($data['slots']) === 3;
            }))
            ->willReturn(new JsonResponse($expectedResponse));

        $response = $this->adminBookingController->getAvailableSlots($request, $encodedName);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testGetAvailableSlotsWithMissingParameters()
    {
        $encodedName = 'relaxing-massage';
        $service = $this->createMock(Service::class);
        
        $expectedResponse = [
            'error' => FlashMessages::MISSING_PARAMETERS_OFFER_EMPLOYEE_DATE
        ];

        $request = $this->createMock(Request::class);
        $request->query = new InputBag([]);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willReturn($service);

        $this->adminBookingController
            ->expects($this->once())
            ->method('json')
            ->with(
                $this->equalTo(['error' => FlashMessages::MISSING_PARAMETERS_OFFER_EMPLOYEE_DATE]),
                $this->equalTo(Response::HTTP_BAD_REQUEST)
            )
            ->willReturn(new JsonResponse($expectedResponse, Response::HTTP_BAD_REQUEST));

        $response = $this->adminBookingController->getAvailableSlots($request, $encodedName);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testGetAvailableSlotsWithServiceNotFound()
    {
        $encodedName = 'non-existent-service';
        $exception = new NotFoundHttpException(FlashMessages::SERVICE_NOT_FOUND);
        
        $expectedResponse = [
            'error' => FlashMessages::SERVICE_NOT_FOUND
        ];

        $request = $this->createMock(Request::class);
        $request->query = new InputBag([
            'offer' => '1',
            'employee' => '2',
            'date' => '2023-06-01'
        ]);

        $this->adminBookingService
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willThrowException($exception);

        $this->adminBookingController
            ->expects($this->once())
            ->method('json')
            ->with(
                $this->equalTo(['error' => FlashMessages::SERVICE_NOT_FOUND]),
                $this->equalTo(Response::HTTP_NOT_FOUND)
            )
            ->willReturn(new JsonResponse($expectedResponse, Response::HTTP_NOT_FOUND));

        $response = $this->adminBookingController->getAvailableSlots($request, $encodedName);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
