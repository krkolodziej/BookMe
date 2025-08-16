<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Repository\EmployeeRepository;
use App\Repository\OpinionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employee')]
class EmployeeController extends AbstractController
{
    #[Route('/{id}', name: 'app_employee_show', methods: ['GET'])]
    public function show(
        Employee $employee,
        EmployeeRepository $employeeRepository,
        OpinionRepository $opinionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $statistics = $employeeRepository->getEmployeeStatistics($employee->getId());

        $offers = $employee->getService()->getOffers();

        $opinions = $opinionRepository->findOpinionsByEmployee($employee->getId());

        return $this->render('employee/show.html.twig', [
            'employee' => $employee,
            'statistics' => $statistics,
            'offers' => $offers,
            'opinions' => $opinions,
        ]);
    }
}