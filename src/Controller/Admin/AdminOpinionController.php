<?php

namespace App\Controller\Admin;

use App\Entity\Opinion;
use App\Entity\Service;
use App\Form\AdminOpinionType;
use App\Repository\OpinionRepository;
use App\Repository\ServiceRepository;
use App\Service\AdminOpinionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/opinion')]
class AdminOpinionController extends AbstractController
{
    public function __construct(
        private readonly AdminOpinionService $adminOpinionService
    ) {
    }

    #[Route('/service/{encodedName}', name: 'admin_opinion_index')]
    public function index(string $encodedName, Request $request): Response
    {
        try {
            $service = $this->adminOpinionService->getServiceByEncodedName($encodedName);
            
            $page = $request->query->getInt('page', 1);
            $pageSize = 10;
            $sorts = $request->query->get('sort', '-createdAt');

            $opinions = $this->adminOpinionService->getServiceOpinions($service, $page, $pageSize, $sorts);

            return $this->render('admin/opinion/index.html.twig', [
                'opinions' => $opinions['items'],
                'total' => $opinions['total'],
                'totalPages' => $opinions['totalPages'],
                'currentPage' => $page,
                'service' => $service,
                'encodedName' => $encodedName,
                'sort' => $sorts
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/edit/{id}', name: 'admin_opinion_edit')]
    public function edit(string $encodedName, int $id, Request $request): Response
    {
        try {
            $service = $this->adminOpinionService->getServiceByEncodedName($encodedName);
            $opinion = $this->adminOpinionService->getOpinionForEdit($id, $service);

            $form = $this->createForm(AdminOpinionType::class, $opinion, [
                'service' => $service
            ]);
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                $this->adminOpinionService->updateOpinion($opinion);
                $this->addFlash('success', 'Opinia została zaktualizowana');
                return $this->redirectToRoute('admin_opinion_index', ['encodedName' => $encodedName]);
            }

            return $this->render('admin/opinion/edit.html.twig', [
                'form' => $form->createView(),
                'opinion' => $opinion,
                'service' => $service,
                'encodedName' => $encodedName
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/delete/{id}', name: 'admin_opinion_delete', methods: ['POST'])]
    public function delete(string $encodedName, int $id, Request $request): Response
    {
        try {
            $service = $this->adminOpinionService->getServiceByEncodedName($encodedName);
            $submittedToken = $request->request->get('_token');
            
            $this->adminOpinionService->deleteOpinion($id, $service, $submittedToken);
            $this->addFlash('success', 'Opinia została usunięta');

            return $this->redirectToRoute('admin_opinion_index', ['encodedName' => $encodedName]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/create', name: 'admin_opinion_create')]
    public function create(string $encodedName, Request $request): Response
    {
        try {
            $service = $this->adminOpinionService->getServiceByEncodedName($encodedName);
            $opinion = $this->adminOpinionService->createOpinion($service);
            
            $opinion->setRating(5);
            
            $form = $this->createForm(AdminOpinionType::class, $opinion, [
                'service' => $service
            ]);
            
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                $this->adminOpinionService->saveOpinion($opinion);
                $this->addFlash('success', 'Opinia została dodana');
                return $this->redirectToRoute('admin_opinion_index', ['encodedName' => $encodedName]);
            }

            return $this->render('admin/opinion/create.html.twig', [
                'form' => $form->createView(),
                'service' => $service,
                'encodedName' => $encodedName
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }
}