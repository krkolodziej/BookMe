<?php

namespace App\Controller\Admin;

use App\Entity\ServiceCategory;
use App\Form\AdminServiceCategoryType;
use App\Service\AdminServiceCategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminServiceCategoryController extends AbstractController
{
    public function __construct(
        private readonly AdminServiceCategoryService $serviceCategoryService,
    ) {}

    #[Route('/service-category', name: 'admin_service_category_index')]
    public function index(Request $request): Response
    {
        $searchTerm = $request->query->get('searchTerm', '');

        if (!empty($searchTerm)) {
            $categories = $this->serviceCategoryService->searchCategoriesByName($searchTerm);
        } else {
            $categories = $this->serviceCategoryService->getAllCategories();
        }

        return $this->render('admin/service_category/index.html.twig', [
            'categories' => $categories,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/service-category/edit/{encodedName}', name: 'admin_service_category_edit')]
    public function edit(Request $request, string $encodedName): Response
    {
        try {
            $category = $this->serviceCategoryService->getCategoryByEncodedName($encodedName);

            $form = $this->createForm(AdminServiceCategoryType::class, $category);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->serviceCategoryService->saveCategory($category, true);
                    $this->addFlash('success', 'Kategoria została zaktualizowana.');
                    return $this->redirectToRoute('admin_service_category_index');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Błąd podczas aktualizacji kategorii: ' . $e->getMessage());
                }
            }

            return $this->render('admin/service_category/edit.html.twig', [
                'category' => $category,
                'form' => $form->createView()
            ]);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_service_category_index');
        }
    }

    #[Route('/service-category/create', name: 'admin_service_category_create')]
    public function create(Request $request): Response
    {
        $category = new ServiceCategory();

        $form = $this->createForm(AdminServiceCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->serviceCategoryService->saveCategory($category, true);
                $this->addFlash('success', 'Kategoria została utworzona.');
                return $this->redirectToRoute('admin_service_category_index');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Błąd podczas tworzenia kategorii: ' . $e->getMessage());
            }
        }

        return $this->render('admin/service_category/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/service-category/delete/{encodedName}', name: 'admin_service_category_delete', methods: ['POST'])]
    public function delete(string $encodedName): Response
    {
        try {
            $category = $this->serviceCategoryService->getCategoryByEncodedName($encodedName);

            try {
                $this->serviceCategoryService->removeCategory($category, true);
                $this->addFlash('success', 'Kategoria została usunięta.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Błąd podczas usuwania kategorii: ' . $e->getMessage());
            }
        } catch (NotFoundHttpException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('admin_service_category_index');
    }
}