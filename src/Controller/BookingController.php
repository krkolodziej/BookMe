<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\EmployeeRepository;
use App\Repository\OfferRepository;
use App\Repository\ServiceRepository;
use App\Service\AvailabilityService;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BookingController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly BookingRepository $bookingRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly OfferRepository $offerRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly AvailabilityService $availabilityService,
        private readonly EntityManagerInterface $entityManager,
        private readonly BookingService $bookingService
    ) {}

    #[Route('/wizyty', name: 'booking_index')]
    public function index(): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedException('Access Denied');
        }

        $employee = $this->employeeRepository->findOneBy(['user' => $user]);
        $isEmployee = $employee !== null;

        if ($isEmployee) {
            return $this->redirectToRoute('employee_bookings');
        }
        
        $bookings = $this->bookingRepository->findByUser($user->getId());

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookings,
            'currentTime' => new \DateTimeImmutable()
        ]);
    }
    
    #[Route('/wizyty/pracownik', name: 'employee_bookings')]
    public function employeeBookings(): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedException('Access Denied');
        }

        $employee = $this->employeeRepository->findOneBy(['user' => $user]);
        
        if (!$employee) {
            throw new AccessDeniedException('Ta strona jest dostępna tylko dla pracowników');
        }
        
        $bookings = $this->bookingRepository->findByEmployee($employee->getId());

        return $this->render('booking/employee_bookings.html.twig', [
            'bookings' => $bookings,
            'employee' => $employee,
            'currentTime' => new \DateTimeImmutable()
        ]);
    }
    
    #[Route('/umow-wizyte/{serviceEncodedName}/{offerEncodedName}', name: 'booking_create')]
    public function create(Request $request, string $serviceEncodedName, string $offerEncodedName): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            $this->addFlash('danger', 'You must be logged in to book a visit.');
            return $this->redirectToRoute('app_login');
        }
        
        try {
            $serviceAndOffer = $this->bookingService->getServiceAndOffer($serviceEncodedName, $offerEncodedName);
            $service = $serviceAndOffer['service'];
            $offer = $serviceAndOffer['offer'];
            
            $employees = $this->employeeRepository->findByServiceEncodedName($serviceEncodedName);
            
            $booking = $this->bookingService->createBooking($user, $service, $offer);
            
            $form = $this->createForm(BookingType::class, $booking, [
                'service' => $service,
                'offer' => $offer,
                'employees' => $employees
            ]);
            
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->bookingService->saveBooking($booking);
                $this->addFlash('success', 'The visit has been successfully booked.');
                return $this->redirectToRoute('booking_index');
            }
            
            return $this->render('booking/create.html.twig', [
                'form' => $form->createView(),
                'service' => $service,
                'offer' => $offer
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute($e->getCode() === 404 ? 'home' : 'service_show', 
                $e->getCode() === 404 ? [] : ['encodedName' => $serviceEncodedName]);
        }
    }
    
    #[Route('/dostepne-terminy/{serviceEncodedName}/{offerEncodedName}', name: 'booking_available_slots', methods: ['GET'])]
    public function getAvailableSlots(Request $request, string $serviceEncodedName, string $offerEncodedName): JsonResponse
    {
        try {
            $employeeId = $request->query->get('employee');
            $date = $request->query->get('date');
            
            if (!$employeeId || !$date) {
                return $this->json([
                    'error' => 'Missing query parameters (employee, date).'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $slots = $this->bookingService->getAvailableSlots($serviceEncodedName, $offerEncodedName, $employeeId, $date);
            
            return $this->json([
                'slots' => array_map(
                    fn(\DateTime $slot) => [
                        'time' => $slot->format('H:i'),
                        'datetime' => $slot->format('Y-m-d\TH:i:s')
                    ],
                    $slots
                )
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], $e->getCode() === 404 ? Response::HTTP_NOT_FOUND : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/edytuj-wizyte/{id}', name: 'booking_edit')]
    public function edit(Request $request, int $id): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            $this->addFlash('danger', 'You must be logged in to edit a visit.');
            return $this->redirectToRoute('app_login');
        }
        
        try {
            $booking = $this->bookingService->getUserBooking($id, $user);
            
            $service = $booking->getService();
            $offer = $booking->getOffer();
            $employees = $this->employeeRepository->findByServiceEncodedName($service->getEncodedName());
            
            $form = $this->createForm(BookingType::class, $booking, [
                'service' => $service,
                'offer' => $offer,
                'employees' => $employees
            ]);
            
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                $this->bookingService->saveBooking($booking);
                $this->addFlash('success', 'The visit has been successfully updated.');
                return $this->redirectToRoute('booking_index');
            }
            
            return $this->render('booking/edit.html.twig', [
                'form' => $form->createView(),
                'booking' => $booking,
                'service' => $service,
                'offer' => $offer
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('booking_index');
        }
    }

    #[Route('/usun/{id}', name: 'booking_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $this->bookingService->deleteBooking($id);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Visit has been cancelled'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() === 404 ? Response::HTTP_NOT_FOUND : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}