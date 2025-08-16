<?php

namespace App\Controller\Admin;

use App\Repository\ServiceRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\EmployeeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private ServiceRepository $serviceRepository,
        private ServiceCategoryRepository $serviceCategoryRepository,
        private EmployeeRepository $employeeRepository,
        private UserRepository $userRepository
    ) {}

    #[Route('/panel/admina', name: 'admin_index')]
    public function index(): Response
    {
        $stats = [
            'totalServices' => $this->serviceRepository->getTotalCount(),
            'activeServices' => $this->serviceRepository->getActiveCount(),
            'totalUsers' => $this->userRepository->getTotalCount(),
            'totalCategories' => $this->serviceCategoryRepository->getTotalCount(),
            'totalEmployees' => $this->employeeRepository->getTotalCount(),
            'activeEmployees' => $this->employeeRepository->getActiveCount(),
        ];

        return $this->render('admin/index.html.twig', [
            'stats' => $stats
        ]);
    }
}