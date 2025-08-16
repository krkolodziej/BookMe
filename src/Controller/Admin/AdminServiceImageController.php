<?php

namespace App\Controller\Admin;

use App\Entity\ServiceImage;
use App\Entity\Service;
use App\Form\AdminServiceImageType;
use App\Service\AdminServiceImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/service-image')]
class AdminServiceImageController extends AbstractController
{
    public function __construct(
        private readonly AdminServiceImageService $serviceImageService
    ) {
    }

    #[Route('/service/{encodedName}', name: 'admin_service_image_index')]
    public function index(string $encodedName, Request $request): Response
    {
        try {
            $service = $this->serviceImageService->getServiceByEncodedName($encodedName);
            $images = $this->serviceImageService->getServiceImages($service);

            return $this->render('admin/service_image/index.html.twig', [
                'images' => $images,
                'service' => $service,
                'encodedName' => $encodedName
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/create', name: 'admin_service_image_create')]
    public function create(string $encodedName, Request $request): Response
    {
        try {
            $service = $this->serviceImageService->getServiceByEncodedName($encodedName);
            $serviceImage = $this->serviceImageService->createServiceImage($service);

            $form = $this->createForm(AdminServiceImageType::class, $serviceImage);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->serviceImageService->saveServiceImage($serviceImage);
                $this->addFlash('success', 'Zdjęcie zostało dodane');
                return $this->redirectToRoute('admin_service_image_index', ['encodedName' => $encodedName]);
            }

            return $this->render('admin/service_image/create.html.twig', [
                'form' => $form->createView(),
                'service' => $service,
                'encodedName' => $encodedName
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/edit/{id}', name: 'admin_service_image_edit')]
    public function edit(string $encodedName, int $id, Request $request): Response
    {
        try {
            $service = $this->serviceImageService->getServiceByEncodedName($encodedName);
            $serviceImage = $this->serviceImageService->getServiceImageForEdit($id, $service);

            $form = $this->createForm(AdminServiceImageType::class, $serviceImage);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->serviceImageService->updateServiceImage($serviceImage);
                $this->addFlash('success', 'Zdjęcie zostało zaktualizowane');
                return $this->redirectToRoute('admin_service_image_index', ['encodedName' => $encodedName]);
            }

            return $this->render('admin/service_image/edit.html.twig', [
                'form' => $form->createView(),
                'serviceImage' => $serviceImage,
                'service' => $service,
                'encodedName' => $encodedName
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/delete/{id}', name: 'admin_service_image_delete', methods: ['POST'])]
    public function delete(string $encodedName, int $id, Request $request): Response
    {
        try {
            $service = $this->serviceImageService->getServiceByEncodedName($encodedName);
            $submittedToken = $request->request->get('_token');
            
            $this->serviceImageService->deleteServiceImage($id, $service, $submittedToken);
            $this->addFlash('success', 'Zdjęcie zostało usunięte');

            return $this->redirectToRoute('admin_service_image_index', ['encodedName' => $encodedName]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
    }
}