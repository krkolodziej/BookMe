<?php

namespace App\Controller\Admin;

use App\Constant\FlashMessages;
use App\Entity\Booking;
use App\Form\AdminBookingType;
use App\Repository\BookingRepository;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use App\Service\AdminBookingService;
use App\Service\AvailabilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Route('/admin/service')]
class AdminBookingController extends AbstractController
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly AvailabilityService $availabilityService,
        private readonly AdminBookingService $adminBookingService
    ) {}

    #[Route('/{encodedName}/bookings', name: 'admin_service_bookings')]
    public function index(string $encodedName): Response
    {
        try {
            $service = $this->adminBookingService->getServiceByEncodedName($encodedName);
            $bookings = $this->adminBookingService->getServiceBookings($encodedName);

            return $this->render('admin/booking/index.html.twig', [
                'service' => $service,
                'bookings' => $bookings
            ]);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_service_index');
        }
    }

    #[Route('/{encodedName}/bookings/new', name: 'admin_service_bookings_new')]
    public function create(Request $request, string $encodedName): Response
    {
        try {
            $service = $this->adminBookingService->getServiceByEncodedName($encodedName);
            $booking = $this->adminBookingService->createBooking($service);
            $employees = $this->adminBookingService->getEmployeesForService($encodedName);

            $form = $this->createForm(AdminBookingType::class, $booking, [
                'service' => $service,
                'employees' => $employees
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->adminBookingService->saveBooking($booking);
                    $this->addFlash('success', FlashMessages::VISIT_CREATED_SUCCESS);
                    return $this->redirectToRoute('admin_service_bookings', ['encodedName' => $encodedName]);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Wystąpił błąd podczas zapisywania wizyty: ' . $e->getMessage());
                }
            }

            return $this->render('admin/booking/create.html.twig', [
                'form' => $form->createView(),
                'service' => $service
            ]);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_service_index');
        }
    }

    #[Route('/{encodedName}/bookings/{id}/edit', name: 'admin_service_bookings_edit')]
    public function edit(Request $request, int $id, string $encodedName): Response
    {
        try {
            $service = $this->adminBookingService->getServiceByEncodedName($encodedName);
            $booking = $this->adminBookingService->getBookingForEdit($id, $service);
            $employees = $this->adminBookingService->getEmployeesForService($encodedName);

            $form = $this->createForm(AdminBookingType::class, $booking, [
                'service' => $service,
                'employees' => $employees
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->adminBookingService->saveBooking($booking);
                    $this->addFlash('success', 'Wizyta została pomyślnie zaktualizowana.');
                    return $this->redirectToRoute('admin_service_bookings', ['encodedName' => $encodedName]);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Wystąpił błąd podczas zapisywania wizyty: ' . $e->getMessage());
                }
            }

            return $this->render('admin/booking/edit.html.twig', [
                'form' => $form->createView(),
                'booking' => $booking,
                'service' => $service
            ]);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute(
                $e->getMessage() === 'Serwis nie został znaleziony.' 
                    ? 'admin_service_index' 
                    : 'admin_service_bookings', 
                $e->getMessage() === 'Serwis nie został znaleziony.' 
                    ? [] 
                    : ['encodedName' => $encodedName]
            );
        }
    }

    #[Route('/{encodedName}/bookings/{id}/delete', name: 'admin_service_bookings_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, string $encodedName): Response
    {
        try {
            $service = $this->adminBookingService->getServiceByEncodedName($encodedName);
            $this->adminBookingService->deleteBooking($id, $service);
            
            $this->addFlash('success', FlashMessages::VISIT_DELETED_SUCCESS);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('danger', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Wystąpił błąd podczas usuwania wizyty: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_service_bookings', ['encodedName' => $encodedName]);
    }

    #[Route('/{encodedName}/bookings/get-available-slots', name: 'admin_service_bookings_slots', methods: ['GET'])]
    public function getAvailableSlots(Request $request, string $encodedName): JsonResponse
    {
        try {
            $service = $this->adminBookingService->getServiceByEncodedName($encodedName);
            
            $offerId = $request->query->get('offer');
            $employeeId = $request->query->get('employee');
            $date = $request->query->get('date');

            if (!$offerId || !$employeeId || !$date) {
                return $this->json([
                    'error' => FlashMessages::MISSING_PARAMETERS_OFFER_EMPLOYEE_DATE
                ], Response::HTTP_BAD_REQUEST);
            }

            $slots = $this->adminBookingService->getAvailableTimeSlots(
                $service, 
                $offerId, 
                $employeeId, 
                $date
            );

            return $this->json([
                'slots' => array_map(
                    fn(\DateTime $slot) => [
                        'time' => $slot->format('H:i'),
                        'datetime' => $slot->format('Y-m-d\TH:i:s')
                    ],
                    $slots
                )
            ]);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => FlashMessages::ERROR_FETCHING_AVAILABLE_TIMES . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}