<?php

namespace App\Controller\Admin;

use App\Entity\Offer;
use App\Entity\Service;
use App\Form\AdminOfferType;
use App\Repository\OfferRepository;
use App\Repository\ServiceRepository;
use App\Service\AdminOfferService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/offer')]
class AdminOfferController extends AbstractController
{
    public function __construct(
        private readonly AdminOfferService $adminOfferService
    ) {
    }

    #[Route('/service/{encodedName}', name: 'admin_offer_index')]
    public function index(string $encodedName, Request $request): Response
    {
        try {
            $service = $this->adminOfferService->getServiceByEncodedName($encodedName);
            
            $page = $request->query->getInt('page', 1);
            $pageSize = 10;
            $sort = $request->query->get('sort', 'name');
            $direction = $request->query->get('direction', 'asc');

            $offers = $this->adminOfferService->getServiceOffers(
                $service, 
                $page, 
                $pageSize, 
                $sort, 
                $direction
            );

            return $this->render('admin/offer/index.html.twig', [
                'offers' => $offers['items'],
                'total' => $offers['total'],
                'totalPages' => $offers['totalPages'],
                'currentPage' => $page,
                'service' => $service,
                'encodedName' => $encodedName,
                'sort' => $sort,
                'direction' => $direction
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/create', name: 'admin_offer_create')]
    public function create(string $encodedName, Request $request): Response
    {
        try {
            $service = $this->adminOfferService->getServiceByEncodedName($encodedName);
            $offer = $this->adminOfferService->createNewOffer($service);
            
            $form = $this->createForm(AdminOfferType::class, $offer);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->adminOfferService->saveOffer($offer);
                $this->addFlash('success', 'Oferta została dodana');
                return $this->redirectToRoute('admin_offer_index', ['encodedName' => $encodedName]);
            }

            return $this->render('admin/offer/create.html.twig', [
                'form' => $form->createView(),
                'service' => $service,
                'encodedName' => $encodedName
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/edit/{id}', name: 'admin_offer_edit')]
    public function edit(string $encodedName, int $id, Request $request): Response
    {
        try {
            $service = $this->adminOfferService->getServiceByEncodedName($encodedName);
            $offer = $this->adminOfferService->getOfferForEdit($id, $service);

            $form = $this->createForm(AdminOfferType::class, $offer);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->adminOfferService->updateOffer($offer);
                $this->addFlash('success', 'Oferta została zaktualizowana');
                return $this->redirectToRoute('admin_offer_index', ['encodedName' => $encodedName]);
            }

            return $this->render('admin/offer/edit.html.twig', [
                'form' => $form->createView(),
                'offer' => $offer,
                'service' => $service,
                'encodedName' => $encodedName
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/delete/{id}', name: 'admin_offer_delete', methods: ['POST'])]
    public function delete(string $encodedName, int $id, Request $request): Response
    {
        try {
            $service = $this->adminOfferService->getServiceByEncodedName($encodedName);
            $submittedToken = $request->request->get('_token');
            
            $this->adminOfferService->deleteOffer($id, $service, $submittedToken);
            
            $this->addFlash('success', 'Oferta została usunięta');
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('admin_offer_index', ['encodedName' => $encodedName]);
    }
}