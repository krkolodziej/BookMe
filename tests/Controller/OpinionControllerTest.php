<?php

namespace App\Tests\Controller;

use App\Controller\OpinionController;
use App\Entity\Booking;
use App\Entity\Opinion;
use App\Entity\Service;
use App\Entity\User;
use App\Form\OpinionType;
use App\Repository\BookingRepository;
use App\Repository\OpinionRepository;
use App\Repository\ServiceRepository;
use App\Service\OpinionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OpinionControllerTest extends TestCase
{
    private $serviceRepository;
    private $opinionRepository;
    private $bookingRepository;
    private $entityManager;
    private $opinionService;
    private $opinionController;

    protected function setUp(): void
    {
        $this->serviceRepository = $this->createMock(ServiceRepository::class);
        $this->opinionRepository = $this->createMock(OpinionRepository::class);
        $this->bookingRepository = $this->createMock(BookingRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->opinionService = $this->createMock(OpinionService::class);

        $this->opinionController = $this->getMockBuilder(OpinionController::class)
            ->setConstructorArgs([
                $this->serviceRepository,
                $this->opinionRepository,
                $this->bookingRepository,
                $this->entityManager,
                $this->opinionService
            ])
            ->onlyMethods(['getUser', 'createForm', 'render', 'redirectToRoute', 'addFlash'])
            ->getMock();
    }

    public function testGetOpinionsSuccess()
    {
        $encodedName = 'relaxing-massage';
        $content = json_encode([
            'page' => 1,
            'pageSize' => 10,
            'sorts' => '-createdAt'
        ]);

        $expectedOpinions = [
            'items' => [
                ['id' => 1, 'content' => 'Great service!', 'rating' => 5],
                ['id' => 2, 'content' => 'Very professional', 'rating' => 4]
            ],
            'totalPages' => 1,
            'currentPage' => 1,
            'pageSize' => 10,
            'total' => 2
        ];

        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn($content);

        $this->opinionService
            ->expects($this->once())
            ->method('getServiceOpinions')
            ->with(
                $this->equalTo($encodedName),
                $this->callback(function ($params) {
                    return isset($params['page']) && isset($params['pageSize']) && isset($params['sorts']);
                })
            )
            ->willReturn($expectedOpinions);

        $response = $this->opinionController->getOpinions($request, $encodedName);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode($expectedOpinions), $response->getContent());
    }

    public function testGetOpinionsWithNotFoundException()
    {
        $encodedName = 'non-existent-service';
        $content = json_encode([]);

        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn($content);

        $this->opinionService
            ->expects($this->once())
            ->method('getServiceOpinions')
            ->willThrowException(new NotFoundHttpException('Service not found'));

        $response = $this->opinionController->getOpinions($request, $encodedName);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Internal server error', $responseData['error']);
        $this->assertEquals('Service not found', $responseData['message']);
    }

    public function testGetOpinionsWithGenericException()
    {
        $encodedName = 'relaxing-massage';
        $content = json_encode([]);

        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn($content);

        $this->opinionService
            ->expects($this->once())
            ->method('getServiceOpinions')
            ->willThrowException(new \Exception('Database error'));

        $response = $this->opinionController->getOpinions($request, $encodedName);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Internal server error', $responseData['error']);
        $this->assertEquals('Database error', $responseData['message']);
    }

    public function testCreateWithInvalidBooking()
    {
        $bookingId = 999;

        $request = $this->createMock(Request::class);

        $this->opinionService
            ->expects($this->once())
            ->method('getBookingForOpinion')
            ->with($this->equalTo($bookingId))
            ->willThrowException(new NotFoundHttpException('Booking not found.'));

        $this->opinionController
            ->expects($this->once())
            ->method('addFlash')
            ->with('error', 'Booking not found.');

        $this->opinionController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('booking_index')
            ->willReturn(new RedirectResponse('/bookings'));

        $response = $this->opinionController->create($request, $bookingId);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testCreateWithValidBookingFormNotSubmitted()
    {
        $bookingId = 1;
        $booking = $this->getMockBuilder(Booking::class)
            ->disableOriginalConstructor()
            ->getMock();
        $opinion = new Opinion();
        $user = new User();
        $formView = new FormView();

        $request = $this->createMock(Request::class);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('getBookingForOpinion')
            ->with($this->equalTo($bookingId))
            ->willReturn($booking);

        $this->opinionService
            ->expects($this->once())
            ->method('createOpinion')
            ->with($this->identicalTo($booking), $this->identicalTo($user))
            ->willReturn($opinion);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        $this->opinionController
            ->expects($this->once())
            ->method('createForm')
            ->with(OpinionType::class, $this->identicalTo($opinion))
            ->willReturn($form);

        $this->opinionController
            ->expects($this->once())
            ->method('render')
            ->with(
                'opinion/create.html.twig',
                $this->callback(function ($params) use ($formView, $booking) {
                    return $params['form'] === $formView && $params['booking'] === $booking;
                })
            )
            ->willReturn(new Response());

        $response = $this->opinionController->create($request, $bookingId);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateWithValidBookingFormSubmittedAndValid()
    {
        $bookingId = 1;
        $booking = $this->getMockBuilder(Booking::class)
            ->disableOriginalConstructor()
            ->getMock();
        $opinion = new Opinion();
        $user = new User();

        $request = $this->createMock(Request::class);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('getBookingForOpinion')
            ->with($this->equalTo($bookingId))
            ->willReturn($booking);

        $this->opinionService
            ->expects($this->once())
            ->method('createOpinion')
            ->with($this->identicalTo($booking), $this->identicalTo($user))
            ->willReturn($opinion);

        $this->opinionService
            ->expects($this->once())
            ->method('saveOpinion')
            ->with($this->identicalTo($opinion));

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $this->opinionController
            ->expects($this->once())
            ->method('createForm')
            ->with(OpinionType::class, $this->identicalTo($opinion))
            ->willReturn($form);

        $this->opinionController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Opinion has been added successfully.');

        $this->opinionController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('booking_index')
            ->willReturn(new RedirectResponse('/bookings'));

        $response = $this->opinionController->create($request, $bookingId);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testEditWhenOpinionNotFound()
    {
        $opinionId = 999;
        $user = new User();

        $request = $this->createMock(Request::class);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('getOpinionForEdit')
            ->with($this->equalTo($opinionId), $this->identicalTo($user))
            ->willThrowException(new NotFoundHttpException('Opinion not found.'));

        $this->opinionController
            ->expects($this->once())
            ->method('addFlash')
            ->with('error', 'Opinion not found.');

        $this->opinionController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('booking_index')
            ->willReturn(new RedirectResponse('/bookings'));

        $response = $this->opinionController->edit($request, $opinionId);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testEditWhenAccessDenied()
    {
        $opinionId = 1;
        $user = new User();

        $request = $this->createMock(Request::class);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('getOpinionForEdit')
            ->with($this->equalTo($opinionId), $this->identicalTo($user))
            ->willThrowException(new AccessDeniedException('You are not allowed to edit this opinion.'));

        $this->opinionController
            ->expects($this->once())
            ->method('addFlash')
            ->with('error', 'You are not allowed to edit this opinion.');

        $this->opinionController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('booking_index')
            ->willReturn(new RedirectResponse('/bookings'));

        $response = $this->opinionController->edit($request, $opinionId);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testEditWhenFormNotSubmitted()
    {
        $opinionId = 1;
        $user = new User();
        $opinion = $this->getMockBuilder(Opinion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $booking = $this->getMockBuilder(Booking::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $opinion->method('getBooking')->willReturn($booking);
        $formView = new FormView();

        $request = $this->createMock(Request::class);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('getOpinionForEdit')
            ->with($this->equalTo($opinionId), $this->identicalTo($user))
            ->willReturn($opinion);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        $this->opinionController
            ->expects($this->once())
            ->method('createForm')
            ->with(OpinionType::class, $this->identicalTo($opinion))
            ->willReturn($form);

        $this->opinionController
            ->expects($this->once())
            ->method('render')
            ->with(
                'opinion/edit.html.twig',
                $this->callback(function ($params) use ($formView, $opinion, $booking) {
                    return $params['form'] === $formView && 
                           $params['opinion'] === $opinion && 
                           $params['booking'] === $booking;
                })
            )
            ->willReturn(new Response());

        $response = $this->opinionController->edit($request, $opinionId);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditWhenFormSubmittedAndValid()
    {
        $opinionId = 1;
        $user = new User();
        $opinion = $this->getMockBuilder(Opinion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $booking = $this->getMockBuilder(Booking::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $opinion->method('getBooking')->willReturn($booking);

        $request = $this->createMock(Request::class);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('getOpinionForEdit')
            ->with($this->equalTo($opinionId), $this->identicalTo($user))
            ->willReturn($opinion);

        $this->opinionService
            ->expects($this->once())
            ->method('saveOpinion')
            ->with($this->identicalTo($opinion));

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $this->opinionController
            ->expects($this->once())
            ->method('createForm')
            ->with(OpinionType::class, $this->identicalTo($opinion))
            ->willReturn($form);

        $this->opinionController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Opinion has been updated successfully.');

        $this->opinionController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('booking_index')
            ->willReturn(new RedirectResponse('/bookings'));

        $response = $this->opinionController->edit($request, $opinionId);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteSuccess()
    {
        $opinionId = 1;
        $user = new User();
        $csrfToken = 'valid-token';

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['X-CSRF-TOKEN' => $csrfToken]);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('deleteOpinion')
            ->with(
                $this->equalTo($opinionId),
                $this->identicalTo($user),
                $this->equalTo($csrfToken)
            );

        $response = $this->opinionController->delete($request, $opinionId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertEquals('Opinion has been deleted successfully.', $responseData['success']);
    }

    public function testDeleteWithNotFoundException()
    {
        $opinionId = 999;
        $user = new User();
        $csrfToken = 'valid-token';

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['X-CSRF-TOKEN' => $csrfToken]);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('deleteOpinion')
            ->willThrowException(new NotFoundHttpException('Opinion not found.'));

        $response = $this->opinionController->delete($request, $opinionId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Opinion not found.', $responseData['error']);
    }

    public function testDeleteWithBadRequestException()
    {
        $opinionId = 1;
        $user = new User();
        $csrfToken = 'invalid-token';

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['X-CSRF-TOKEN' => $csrfToken]);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('deleteOpinion')
            ->willThrowException(new BadRequestHttpException('Invalid CSRF token.'));

        $response = $this->opinionController->delete($request, $opinionId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid CSRF token.', $responseData['error']);
    }

    public function testDeleteWithAccessDeniedException()
    {
        $opinionId = 1;
        $user = new User();
        $csrfToken = 'valid-token';

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['X-CSRF-TOKEN' => $csrfToken]);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('deleteOpinion')
            ->willThrowException(new AccessDeniedException('You are not allowed to delete this opinion.'));

        $response = $this->opinionController->delete($request, $opinionId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('You are not allowed to delete this opinion.', $responseData['error']);
    }

    public function testDeleteWithGenericException()
    {
        $opinionId = 1;
        $user = new User();
        $csrfToken = 'valid-token';

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['X-CSRF-TOKEN' => $csrfToken]);

        $this->opinionController
            ->method('getUser')
            ->willReturn($user);

        $this->opinionService
            ->expects($this->once())
            ->method('deleteOpinion')
            ->willThrowException(new \Exception('An error occurred.'));

        $response = $this->opinionController->delete($request, $opinionId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('An error occurred.', $responseData['error']);
    }
}
