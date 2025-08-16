<?php

namespace App\Controller\Admin;

use App\Entity\Employee;
use App\Entity\User;
use App\Form\AdminUserType;
use App\Service\AdminUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly AdminUserService $userService
    ) {
    }

    #[Route('/', name: 'admin_user_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $pageSize = 10;
        $sort = $request->query->get('sort', 'lastName');
        $direction = $request->query->get('direction', 'asc');
        $searchTerm = $request->query->get('search', '');

        $users = $this->userService->getAllUsers($searchTerm, $page, $pageSize, $sort, $direction);

        return $this->render('admin/user/index.html.twig', [
            'users' => $users['items'],
            'total' => $users['total'],
            'totalPages' => $users['totalPages'],
            'currentPage' => $page,
            'sort' => $sort,
            'direction' => $direction,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/create', name: 'admin_user_create')]
    public function create(Request $request): Response
    {
        $user = $this->userService->createUser();

        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userService->hashPassword($user, $user->getPassword());

            $createAsEmployee = $form->get('createAsEmployee')->getData();
            $selectedService = $form->get('service')->getData();

            $this->userService->saveUser($user, true);

            if ($createAsEmployee && $selectedService) {
                try {
                    $employee = $this->userService->createEmployeeForUser($user, $selectedService->getId());
                    
                    $this->userService->saveEmployee($employee, true);

                    $this->addFlash('success', 'Użytkownik został utworzony jako pracownik serwisu');

                    return $this->redirectToRoute('admin_employee_index', [
                        'encodedName' => $selectedService->getEncodedName()
                    ]);
                } catch (NotFoundHttpException $e) {
                    $this->addFlash('danger', $e->getMessage());
                    return $this->redirectToRoute('admin_user_index');
                }
            }

            $this->addFlash('success', 'Użytkownik został utworzony');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edit/{id}', name: 'admin_user_edit')]
    public function edit(int $id, Request $request): Response
    {
        try {
            $user = $this->userService->getUserById($id);

            $form = $this->createForm(AdminUserType::class, $user, [
                'is_edit' => true
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $plainPassword = $form->get('plainPassword')->getData();
                if ($plainPassword) {
                    $this->userService->hashPassword($user, $plainPassword);
                }

                $this->userService->updateUser($user);
                $this->addFlash('success', 'Dane użytkownika zostały zaktualizowane');
                return $this->redirectToRoute('admin_user_index');
            }

            return $this->render('admin/user/edit.html.twig', [
                'form' => $form->createView(),
                'user' => $user
            ]);
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route('/delete/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        try {
            $submittedToken = $request->request->get('_token');
            $this->userService->deleteUser($id, $submittedToken);
            
            $this->addFlash('success', 'Użytkownik został usunięty');
            return $this->redirectToRoute('admin_user_index');
        } catch (NotFoundHttpException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (AccessDeniedException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
    }
}