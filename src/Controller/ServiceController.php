<?php

namespace App\Controller;

use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\ServiceImageRepository;
use App\Repository\ServiceRepository;
use App\Service\ServiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class ServiceController extends AbstractController
{
    public function __construct(
        private readonly ServiceService $serviceService,
        private readonly Security $security
    ) {}

    #[Route('/admin/service/{encodedName}', name: 'admin_service_show')]
    public function adminShow(string $encodedName): Response
    {
        return $this->showService($encodedName, false);
    }

    #[Route('/service/{encodedName}', name: 'service_show')]
    public function show(string $encodedName): Response
    {
        $isEmployee = false;
        if ($this->security->getUser()) {
            $isEmployee = $this->isGranted('ROLE_EMPLOYEE');
        }
        
        return $this->showService($encodedName, $isEmployee);
    }
    
    private function showService(string $encodedName, bool $isEmployee): Response
    {
        $service = $this->serviceService->getServiceByEncodedName($encodedName);

        if (!$service) {
            return $this->redirectToRoute('home');
        }

        $ratingData = $this->serviceService->calculateAverageRating($service);
        $serviceImages = $this->serviceService->getServiceImages($service->getId());

        return $this->render('service/show.html.twig', [
            'service' => $service,
            'averageRating' => $ratingData['averageRating'],
            'opinionsCount' => $ratingData['opinionsCount'],
            'serviceImages' => $serviceImages,
            'isEmployee' => $isEmployee,
        ]);
    }
}