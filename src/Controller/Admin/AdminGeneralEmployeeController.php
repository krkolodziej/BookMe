<?php

namespace App\Controller\Admin;

use App\Entity\Employee;
use App\Entity\User;
use App\Form\AdminGeneralEmployeeType;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Service\AdminEmployeeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/employees')]
class AdminGeneralEmployeeController extends AbstractController
{
    public function __construct(
        private readonly AdminEmployeeService $adminEmployeeService
    ) {
    }

    #[Route('/', name: 'admin_general_employee_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $pageSize = 10;
        $sort = $request->query->get('sort', 'lastName');
        $direction = $request->query->get('direction', 'asc');
        $searchTerm = $request->query->get('search', '');

        $employees = $this->adminEmployeeService->getAllEmployees(
            $searchTerm, 
            $page, 
            $pageSize, 
            $sort, 
            $direction
        );

        return $this->render('admin/general_employee/index.html.twig', [
            'employees' => $employees['items'],
            'total' => $employees['total'],
            'totalPages' => $employees['totalPages'],
            'currentPage' => $page,
            'sort' => $sort,
            'direction' => $direction,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/create', name: 'admin_general_employee_create')]
    public function create(Request $request): Response
    {
        try {
            $employeeData = $this->adminEmployeeService->createNewGeneralEmployee();
            
            $form = $this->createForm(AdminGeneralEmployeeType::class, $employeeData);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                $user = $formData['user'];
                $employee = $formData['employee'];

                $this->adminEmployeeService->checkAndSaveNewEmployee(
                    $user, 
                    $employee, 
                    $user->getPassword()
                );

                $this->addFlash('success', 'Pracownik został dodany');
                return $this->redirectToRoute('admin_general_employee_index');
            }

            return $this->render('admin/general_employee/create.html.twig', [
                'form' => $form->createView()
            ]);
        } catch (BadRequestHttpException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('admin/general_employee/create.html.twig', [
                'form' => $form->createView()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd: ' . $e->getMessage());
            return $this->redirectToRoute('admin_general_employee_index');
        }
    }

    #[Route('/edit/{id}', name: 'admin_general_employee_edit')]
    public function edit(int $id, Request $request): Response
    {
        try {
            $employee = $this->adminEmployeeService->getEmployeeById($id);
            $user = $employee->getUser();

            $form = $this->createForm(AdminGeneralEmployeeType::class, [
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
                return $this->redirectToRoute('admin_general_employee_index');
            }

            return $this->render('admin/general_employee/edit.html.twig', [
                'form' => $form->createView(),
                'employee' => $employee
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd: ' . $e->getMessage());
            return $this->redirectToRoute('admin_general_employee_index');
        }
    }

    #[Route('/delete/{id}', name: 'admin_general_employee_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        try {
            $submittedToken = $request->request->get('_token');
            $this->adminEmployeeService->deleteGeneralEmployee($id, $submittedToken);
            
            $this->addFlash('success', 'Pracownik został usunięty');
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_general_employee_index');
    }
}