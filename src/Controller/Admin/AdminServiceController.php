<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use App\Form\ServiceType;
use App\Service\AdminServiceService;
use App\Service\AdminServiceCategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminServiceController extends AbstractController
{
    public function __construct(
        private readonly AdminServiceService $serviceService,
        private readonly AdminServiceCategoryService $categoryService,
    ) {}

    #[Route('/service', name: 'admin_service_index')]
    public function index(Request $request): Response
    {
        $searchTerm = $request->query->get('searchTerm', '');
        $categoryId = $request->query->get('category');

        if (!empty($searchTerm)) {
            $services = $this->serviceService->searchServicesByName($searchTerm);
        } else {
            $services = $this->serviceService->getAllServices();
        }

        // Filter by category if specified
        if ($categoryId) {
            $services = array_filter($services, function($service) use ($categoryId) {
                return $service->getServiceCategory() && $service->getServiceCategory()->getId() == $categoryId;
            });
        }

        $categories = $this->categoryService->getAllCategories();

        return $this->render('admin/service/index.html.twig', [
            'services' => $services,
            'categories' => $categories,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/service/edit/{encodedName}', name: 'admin_service_edit')]
    public function edit(Request $request, string $encodedName): Response
    {
        try {
            $service = $this->serviceService->getServiceByEncodedNameWithDetails($encodedName);

            $form = $this->createForm(ServiceType::class, $service);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->serviceService->saveService($service, true);
                    $this->addFlash('success', 'Serwis został zaktualizowany.');
                    return $this->redirectToRoute('admin_service_index');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Błąd podczas aktualizacji serwisu: ' . $e->getMessage());
                }
            }

            return $this->render('admin/service/edit.html.twig', [
                'service' => $service,
                'form' => $form->createView()
            ]);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_service_index');
        }
    }

    #[Route('/service/create', name: 'admin_service_create')]
    public function create(Request $request): Response
    {
        $service = new Service();

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->serviceService->saveService($service, true);
                $this->addFlash('success', 'Serwis został utworzony.');
                return $this->redirectToRoute('admin_service_index');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Błąd podczas tworzenia serwisu: ' . $e->getMessage());
            }
        }

        return $this->render('admin/service/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/service/delete/{encodedName}', name: 'admin_service_delete', methods: ['POST'])]
    public function delete(string $encodedName): Response
    {
        try {
            $service = $this->serviceService->getServiceByEncodedName($encodedName);

            try {
                $this->serviceService->removeService($service, true);
                $this->addFlash('success', 'Serwis został usunięty.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Błąd podczas usuwania serwisu: ' . $e->getMessage());
            }
        } catch (NotFoundHttpException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('admin_service_index');
    }
}