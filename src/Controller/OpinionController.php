<?php

namespace App\Controller;

use App\Constant\FlashMessages;
use App\Entity\Booking;
use App\Entity\Opinion;
use App\Form\OpinionType;
use App\Repository\BookingRepository;
use App\Repository\OpinionRepository;
use App\Repository\ServiceRepository;
use App\Service\OpinionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OpinionController extends AbstractController
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository,
        private readonly OpinionRepository $opinionRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly OpinionService $opinionService
    ) {}

    #[Route('/api/opinions/{encodedName}', name: 'opinions_get', methods: ['POST'])]
    public function getOpinions(Request $request, string $encodedName): JsonResponse
    {
        try {
            $content = json_decode($request->getContent(), true);
            $result = $this->opinionService->getServiceOpinions($encodedName, $content);
            
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => FlashMessages::INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage()
            ], $e->getCode() === 404 ? 404 : 500);
        }
    }

    #[Route('/opinions/create/{id}', name: 'opinion_create', methods: ['GET', 'POST'])]
    public function create(Request $request, int $id): Response
    {
        try {
            $booking = $this->opinionService->getBookingForOpinion($id);
            $opinion = $this->opinionService->createOpinion($booking, $this->getUser());
            
            $form = $this->createForm(OpinionType::class, $opinion);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->opinionService->saveOpinion($opinion);
                $this->addFlash('success', FlashMessages::OPINION_ADDED_SUCCESS);
                return $this->redirectToRoute('booking_index');
            }

            return $this->render('opinion/create.html.twig', [
                'form' => $form->createView(),
                'booking' => $booking,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('booking_index');
        }
    }

    #[Route('/opinions/edit/{id}', name: 'opinion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        try {
            $opinion = $this->opinionService->getOpinionForEdit($id, $this->getUser());
            $booking = $opinion->getBooking();
            
            $form = $this->createForm(OpinionType::class, $opinion);
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                $this->opinionService->saveOpinion($opinion);
                $this->addFlash('success', FlashMessages::OPINION_UPDATED_SUCCESS);
                return $this->redirectToRoute('booking_index');
            }

            return $this->render('opinion/edit.html.twig', [
                'form' => $form->createView(),
                'opinion' => $opinion,
                'booking' => $booking,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('booking_index');
        }
    }

    #[Route('/opinions/delete/{id}', name: 'opinion_delete', methods: ['DELETE'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        try {
            $submittedToken = $request->headers->get('X-CSRF-TOKEN');
            $this->opinionService->deleteOpinion($id, $this->getUser(), $submittedToken);
            
            return new JsonResponse(['success' => FlashMessages::OPINION_DELETED_SUCCESS]);
        } catch (\Exception $e) {
            $statusCode = match (get_class($e)) {
                'Symfony\Component\HttpKernel\Exception\NotFoundHttpException' => 404,
                'Symfony\Component\HttpKernel\Exception\BadRequestHttpException' => 400,
                'Symfony\Component\HttpKernel\Exception\AccessDeniedException' => 403,
                default => 500
            };
            
            return new JsonResponse(['error' => $e->getMessage()], $statusCode);
        }
    }
}
