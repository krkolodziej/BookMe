<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\AdminUserController;
use App\Entity\Service;
use App\Entity\User;
use App\Form\AdminUserType;
use App\Service\AdminUserService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminUserControllerTest extends TestCase
{
    private $userService;
    private $adminUserController;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(AdminUserService::class);

        $this->adminUserController = $this->getMockBuilder(AdminUserController::class)
            ->setConstructorArgs([$this->userService])
            ->onlyMethods(['render', 'redirectToRoute', 'createForm', 'addFlash', 'createNotFoundException', 'createAccessDeniedException'])
            ->getMock();
    }

    public function testIndex()
    {
        // Przygotowanie danych
        $request = $this->createMock(Request::class);
        $request->query = new InputBag([
            'page' => 2,
            'sort' => 'firstName',
            'direction' => 'desc',
            'search' => 'Jan'
        ]);
        
        $users = [
            'items' => [$this->createMock(User::class)],
            'total' => 15,
            'totalPages' => 2
        ];

        // Konfiguracja mocka serwisu
        $this->userService
            ->expects($this->once())
            ->method('getAllUsers')
            ->with('Jan', 2, 10, 'firstName', 'desc')
            ->willReturn($users);

        // Konfiguracja mocka render
        $this->adminUserController
            ->expects($this->once())
            ->method('render')
            ->with(
                'admin/user/index.html.twig',
                [
                    'users' => $users['items'],
                    'total' => $users['total'],
                    'totalPages' => $users['totalPages'],
                    'currentPage' => 2,
                    'sort' => 'firstName',
                    'direction' => 'desc',
                    'searchTerm' => 'Jan'
                ]
            )
            ->willReturn(new Response());

        // Wywołanie metody kontrolera
        $response = $this->adminUserController->index($request);

        // Weryfikacja odpowiedzi
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreate()
    {
        // Przygotowanie danych
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $formView = new FormView();

        // Konfiguracja mocka serwisu
        $this->userService
            ->expects($this->once())
            ->method('createUser')
            ->willReturn($user);

        // Konfiguracja mocka formularza
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        // Konfiguracja mocka createForm
        $this->adminUserController
            ->expects($this->once())
            ->method('createForm')
            ->with(AdminUserType::class, $user)
            ->willReturn($form);

        // Konfiguracja mocka render
        $this->adminUserController
            ->expects($this->once())
            ->method('render')
            ->with(
                'admin/user/create.html.twig',
                ['form' => $formView]
            )
            ->willReturn(new Response());

        // Wywołanie metody kontrolera
        $response = $this->adminUserController->create($request);

        // Weryfikacja odpowiedzi
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateWithFormSubmitted()
    {
        // Przygotowanie danych
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $plainPassword = 'password123';

        // Konfiguracja mocka serwisu
        $this->userService
            ->expects($this->once())
            ->method('createUser')
            ->willReturn($user);

        // Konfiguracja mocka formularza
        $createAsEmployeeField = $this->createMock(FormInterface::class);
        $createAsEmployeeField->method('getData')->willReturn(false);

        $plainPasswordField = $this->createMock(FormInterface::class);
        $plainPasswordField->method('getData')->willReturn($plainPassword);

        $serviceField = $this->createMock(FormInterface::class);
        $serviceField->method('getData')->willReturn(null);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('get')
            ->willReturnCallback(function($field) use ($createAsEmployeeField, $serviceField, $plainPasswordField) {
                $map = [
                    'createAsEmployee' => $createAsEmployeeField,
                    'service' => $serviceField,
                    'password' => $plainPasswordField
                ];
                return $map[$field] ?? null;
            });

        // Konfiguracja mocka user
        $user->method('getPassword')->willReturn($plainPassword);

        // Konfiguracja mocka createForm
        $this->adminUserController
            ->expects($this->once())
            ->method('createForm')
            ->with(AdminUserType::class, $user)
            ->willReturn($form);

        // Konfiguracja mocka serwisu dla hasła
        $this->userService
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword);

        // Konfiguracja mocka serwisu dla zapisu
        $this->userService
            ->expects($this->once())
            ->method('saveUser')
            ->with($user, true);

        // Konfiguracja mocka addFlash
        $this->adminUserController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Użytkownik został utworzony');

        // Konfiguracja mocka redirectToRoute
        $this->adminUserController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_user_index')
            ->willReturn(new RedirectResponse('/admin/users'));

        // Wywołanie metody kontrolera
        $response = $this->adminUserController->create($request);

        // Weryfikacja odpowiedzi
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testCreateAsEmployeeWithFormSubmitted()
    {
        // Przygotowanie danych
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $service = $this->createMock(Service::class);
        $serviceId = 1;
        $plainPassword = 'password123';
        $encodedName = 'masaz-relaksacyjny';

        // Konfiguracja mocka service
        $service->method('getId')->willReturn($serviceId);
        $service->method('getEncodedName')->willReturn($encodedName);

        // Konfiguracja mocka serwisu
        $this->userService
            ->expects($this->once())
            ->method('createUser')
            ->willReturn($user);

        // Konfiguracja mocka formularza
        $createAsEmployeeField = $this->createMock(FormInterface::class);
        $createAsEmployeeField->method('getData')->willReturn(true);

        $plainPasswordField = $this->createMock(FormInterface::class);
        $plainPasswordField->method('getData')->willReturn($plainPassword);

        $serviceField = $this->createMock(FormInterface::class);
        $serviceField->method('getData')->willReturn($service);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('get')
            ->willReturnCallback(function($field) use ($createAsEmployeeField, $serviceField, $plainPasswordField) {
                $map = [
                    'createAsEmployee' => $createAsEmployeeField,
                    'service' => $serviceField,
                    'password' => $plainPasswordField
                ];
                return $map[$field] ?? null;
            });

        // Konfiguracja mocka user
        $user->method('getPassword')->willReturn($plainPassword);

        // Konfiguracja mocka createForm
        $this->adminUserController
            ->expects($this->once())
            ->method('createForm')
            ->with(AdminUserType::class, $user)
            ->willReturn($form);

        // Konfiguracja mocka serwisu dla hasła
        $this->userService
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword);

        // Konfiguracja mocka serwisu dla zapisu
        $this->userService
            ->expects($this->once())
            ->method('saveUser')
            ->with($user, true);

        // Konfiguracja mocka serwisu dla utworzenia pracownika
        $employee = $this->createMock(\App\Entity\Employee::class);
        $this->userService
            ->expects($this->once())
            ->method('createEmployeeForUser')
            ->with($user, $serviceId)
            ->willReturn($employee);

        // Konfiguracja mocka serwisu dla zapisu pracownika
        $this->userService
            ->expects($this->once())
            ->method('saveEmployee')
            ->with($employee, true);

        // Konfiguracja mocka addFlash
        $this->adminUserController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Użytkownik został utworzony jako pracownik serwisu');

        // Konfiguracja mocka redirectToRoute
        $this->adminUserController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_employee_index', ['encodedName' => $encodedName])
            ->willReturn(new RedirectResponse('/admin/service/' . $encodedName . '/employees'));

        // Wywołanie metody kontrolera
        $response = $this->adminUserController->create($request);

        // Weryfikacja odpowiedzi
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testEdit()
    {
        // Przygotowanie danych
        $userId = 1;
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $formView = new FormView();

        // Konfiguracja mocka serwisu
        $this->userService
            ->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willReturn($user);

        // Konfiguracja mocka formularza
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        // Konfiguracja mocka createForm
        $this->adminUserController
            ->expects($this->once())
            ->method('createForm')
            ->with(AdminUserType::class, $user, ['is_edit' => true])
            ->willReturn($form);

        // Konfiguracja mocka render
        $this->adminUserController
            ->expects($this->once())
            ->method('render')
            ->with(
                'admin/user/edit.html.twig',
                [
                    'form' => $formView,
                    'user' => $user
                ]
            )
            ->willReturn(new Response());

        // Wywołanie metody kontrolera
        $response = $this->adminUserController->edit($userId, $request);

        // Weryfikacja odpowiedzi
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditWithFormSubmitted()
    {
        // Przygotowanie danych
        $userId = 1;
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $plainPassword = 'new_password';

        // Konfiguracja mocka serwisu
        $this->userService
            ->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willReturn($user);

        // Konfiguracja mocka formularza
        $plainPasswordField = $this->createMock(FormInterface::class);
        $plainPasswordField->method('getData')->willReturn($plainPassword);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('get')
            ->willReturnCallback(function($field) use ($plainPasswordField) {
                $map = [
                    'plainPassword' => $plainPasswordField
                ];
                return $map[$field] ?? null;
            });

        // Konfiguracja mocka createForm
        $this->adminUserController
            ->expects($this->once())
            ->method('createForm')
            ->with(AdminUserType::class, $user, ['is_edit' => true])
            ->willReturn($form);

        // Konfiguracja mocka serwisu dla hasła
        $this->userService
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword);

        // Konfiguracja mocka serwisu dla aktualizacji
        $this->userService
            ->expects($this->once())
            ->method('updateUser')
            ->with($user);

        // Konfiguracja mocka addFlash
        $this->adminUserController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Dane użytkownika zostały zaktualizowane');

        // Konfiguracja mocka redirectToRoute
        $this->adminUserController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_user_index')
            ->willReturn(new RedirectResponse('/admin/users'));

        // Wywołanie metody kontrolera
        $response = $this->adminUserController->edit($userId, $request);

        // Weryfikacja odpowiedzi
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testEditUserNotFound()
    {
        // Przygotowanie danych
        $userId = 999;
        $request = $this->createMock(Request::class);
        $exception = new NotFoundHttpException('Nie znaleziono użytkownika o podanym identyfikatorze');

        // Konfiguracja mocka serwisu
        $this->userService
            ->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willThrowException($exception);

        // Konfiguracja mocka createNotFoundException
        $this->adminUserController
            ->expects($this->once())
            ->method('createNotFoundException')
            ->with('Nie znaleziono użytkownika o podanym identyfikatorze')
            ->willThrowException($exception);

        // Oczekiwanie na wyjątek
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono użytkownika o podanym identyfikatorze');

        // Wywołanie metody kontrolera
        $this->adminUserController->edit($userId, $request);
    }

    public function testDelete()
    {
        // Przygotowanie danych
        $userId = 1;
        $csrfToken = 'valid_token';
        
        $request = $this->createMock(Request::class);
        $request->request = new InputBag(['_token' => $csrfToken]);

        // Konfiguracja mocka serwisu
        $this->userService
            ->expects($this->once())
            ->method('deleteUser')
            ->with($userId, $csrfToken);

        // Konfiguracja mocka addFlash
        $this->adminUserController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Użytkownik został usunięty');

        // Konfiguracja mocka redirectToRoute
        $this->adminUserController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_user_index')
            ->willReturn(new RedirectResponse('/admin/users'));

        // Wywołanie metody kontrolera
        $response = $this->adminUserController->delete($userId, $request);

        // Weryfikacja odpowiedzi
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
} 