<?php

namespace App\Controller\Admin;

use App\Entity\OpeningHour;
use App\Entity\Service;
use App\Form\OpeningHourType;
use App\Repository\OpeningHourRepository;
use App\Repository\ServiceRepository;
use App\Service\AdminOpeningHoursService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/service/{encodedName}/opening-hours')]
class AdminOpeningHoursController extends AbstractController
{
    public function __construct(
        private readonly AdminOpeningHoursService $adminOpeningHoursService
    ) {
    }

    #[Route('/', name: 'admin_opening_hours_index')]
    public function index(string $encodedName): Response
    {
        try {
            $service = $this->adminOpeningHoursService->getServiceByEncodedName($encodedName);
            $openingHours = $this->adminOpeningHoursService->getOpeningHoursForService($encodedName);
            $availableDays = $this->adminOpeningHoursService->getAvailableDays($openingHours);

            return $this->render('admin/openinghours/index.html.twig', [
                'service' => $service,
                'encodedName' => $encodedName,
                'openingHours' => $openingHours,
                'availableDays' => $availableDays
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/new', name: 'admin_opening_hours_new')]
    public function new(Request $request, string $encodedName): Response
    {
        try {
            $service = $this->adminOpeningHoursService->getServiceByEncodedName($encodedName);
            $openingHour = $this->adminOpeningHoursService->createOpeningHour($service);
            
            $existingDays = $this->adminOpeningHoursService->getExistingDaysForService($service->getId());

            $form = $this->createForm(OpeningHourType::class, $openingHour, [
                'exclude_days' => $existingDays
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->adminOpeningHoursService->saveOpeningHour($openingHour);
                    $this->addFlash('success', 'Godziny otwarcia zostały dodane');
                    return $this->redirectToRoute('admin_opening_hours_index', ['encodedName' => $encodedName]);
                } catch (BadRequestHttpException $e) {
                    $this->addFlash('danger', $e->getMessage());
                    return $this->redirectToRoute('admin_opening_hours_index', ['encodedName' => $encodedName]);
                }
            }

            return $this->render('admin/openinghours/create.html.twig', [
                'service' => $service,
                'encodedName' => $encodedName,
                'form' => $form->createView()
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/{id}/edit', name: 'admin_opening_hours_edit')]
    public function edit(Request $request, string $encodedName, int $id): Response
    {
        try {
            $service = $this->adminOpeningHoursService->getServiceByEncodedName($encodedName);
            $openingHour = $this->adminOpeningHoursService->getOpeningHourForEdit($id, $service);
            
            $currentDay = $openingHour->getDayOfWeek();
            
            $existingDays = $this->adminOpeningHoursService->getExistingDaysForService($service->getId(), $id);

            $form = $this->createForm(OpeningHourType::class, $openingHour, [
                'exclude_days' => $existingDays,
                'is_edit' => true
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->adminOpeningHoursService->updateOpeningHour($openingHour, $currentDay);
                    $this->addFlash('success', 'Godziny otwarcia zostały zaktualizowane');
                    return $this->redirectToRoute('admin_opening_hours_index', ['encodedName' => $encodedName]);
                } catch (BadRequestHttpException $e) {
                    $this->addFlash('danger', $e->getMessage());
                    return $this->redirectToRoute('admin_opening_hours_edit', [
                        'encodedName' => $encodedName,
                        'id' => $id
                    ]);
                }
            }

            return $this->render('admin/openinghours/edit.html.twig', [
                'service' => $service,
                'encodedName' => $encodedName,
                'openingHour' => $openingHour,
                'form' => $form->createView()
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/{id}/delete', name: 'admin_opening_hours_delete', methods: ['POST'])]
    public function delete(Request $request, string $encodedName, int $id): Response
    {
        try {
            $service = $this->adminOpeningHoursService->getServiceByEncodedName($encodedName);
            $token = $request->request->get('_token');
            
            $this->adminOpeningHoursService->deleteOpeningHour($id, $service, $token);
            
            $this->addFlash('success', 'Godziny otwarcia zostały usunięte');
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('admin_opening_hours_index', ['encodedName' => $encodedName]);
    }
}