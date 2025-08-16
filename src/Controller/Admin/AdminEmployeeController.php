<?php

namespace App\Controller\Admin;

use App\Entity\Employee;
use App\Entity\Service;
use App\Entity\User;
use App\Form\AdminEmployeeType;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Service\AdminEmployeeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/employee')]
class AdminEmployeeController extends AbstractController
{
    public function __construct(
        private readonly AdminEmployeeService $adminEmployeeService
    ) {
    }

    #[Route('/service/{encodedName}', name: 'admin_employee_index')]
    public function index(string $encodedName, Request $request): Response
    {
        try {
            $service = $this->adminEmployeeService->getServiceByEncodedName($encodedName);
            
            $page = $request->query->getInt('page', 1);
            $pageSize = 10;
            $sort = $request->query->get('sort', 'lastName');
            $direction = $request->query->get('direction', 'asc');

            $employees = $this->adminEmployeeService->getServiceEmployees(
                $service, 
                $page, 
                $pageSize, 
                $sort, 
                $direction
            );

            return $this->render('admin/employee/index.html.twig', [
                'employees' => $employees['items'],
                'total' => $employees['total'],
                'totalPages' => $employees['totalPages'],
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

    #[Route('/service/{encodedName}/create', name: 'admin_employee_create')]
    public function create(string $encodedName, Request $request): Response
    {
        try {
            $service = $this->adminEmployeeService->getServiceByEncodedName($encodedName);
            
            $employeeData = $this->adminEmployeeService->createNewEmployee($service);
            
            $form = $this->createForm(AdminEmployeeType::class, $employeeData);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                $user = $formData['user'];
                $employee = $formData['employee'];
                
                $this->adminEmployeeService->saveNewEmployee(
                    $user, 
                    $employee, 
                    $service, 
                    $user->getPassword()
                );

                $this->addFlash('success', 'Pracownik został dodany');
                return $this->redirectToRoute('admin_employee_index', ['encodedName' => $encodedName]);
            }

            return $this->render('admin/employee/create.html.twig', [
                'form' => $form->createView(),
                'service' => $service,
                'encodedName' => $encodedName
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/edit/{id}', name: 'admin_employee_edit')]
    public function edit(string $encodedName, int $id, Request $request): Response
    {
        try {
            $service = $this->adminEmployeeService->getServiceByEncodedName($encodedName);
            $employee = $this->adminEmployeeService->getEmployeeForEdit($id, $service);
            $user = $employee->getUser();

            $form = $this->createForm(AdminEmployeeType::class, [
                'user' => $user,
                'employee' => $employee
            ], [
                'is_edit' => true
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                $user = $formData['user'];
                
                $plainPassword = $form->get('user')->get('plainPassword')->getData();
                $this->adminEmployeeService->updateEmployee($user, $plainPassword);
                
                $this->addFlash('success', 'Dane pracownika zostały zaktualizowane');
                return $this->redirectToRoute('admin_employee_index', ['encodedName' => $encodedName]);
            }

            return $this->render('admin/employee/edit.html.twig', [
                'form' => $form->createView(),
                'employee' => $employee,
                'service' => $service,
                'encodedName' => $encodedName
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
    }

    #[Route('/service/{encodedName}/delete/{id}', name: 'admin_employee_delete', methods: ['POST'])]
    public function delete(string $encodedName, int $id, Request $request): Response
    {
        try {
            $service = $this->adminEmployeeService->getServiceByEncodedName($encodedName);
            $submittedToken = $request->request->get('_token');
            
            $this->adminEmployeeService->deleteEmployee($id, $service, $submittedToken);
            
            $this->addFlash('success', 'Pracownik został usunięty z serwisu');
            
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('admin_employee_index', ['encodedName' => $encodedName]);
    }
}