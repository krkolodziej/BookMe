<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use App\Entity\User;
use App\Form\UserType;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserControllerTest extends TestCase
{
    private $userService;
    private $userController;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserService::class);

        $this->userController = $this->getMockBuilder(UserController::class)
            ->setConstructorArgs([$this->userService])
            ->onlyMethods(['getUser', 'createForm', 'render', 'redirectToRoute', 'addFlash'])
            ->getMock();
    }

    public function testEditWhenUserNotLoggedIn()
    {
        $this->userController
            ->method('getUser')
            ->willReturn(null);
        
        $this->userController
            ->expects($this->once())
            ->method('addFlash')
            ->with('error', $this->anything());
        
        $this->userController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('home')
            ->willReturn(new RedirectResponse('/'));
        
        $response = $this->userController->edit(new Request());
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testEditWhenUserLoggedInAndFormNotSubmitted()
    {
        $user = $this->createMock(User::class);
        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(FormView::class);
        
        $this->userController
            ->method('getUser')
            ->willReturn($user);
        
        $this->userController
            ->expects($this->once())
            ->method('createForm')
            ->with(UserType::class, $user)
            ->willReturn($form);
        
        $form->method('handleRequest')->with($this->isInstanceOf(Request::class));
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);
        
        $this->userController
            ->expects($this->once())
            ->method('render')
            ->with($this->equalTo('user/edit.html.twig'), $this->arrayHasKey('form'))
            ->willReturn(new Response());
        
        $response = $this->userController->edit(new Request());
        
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditWhenUserLoggedInAndFormSubmittedAndValid()
    {
        $user = $this->createMock(User::class);
        $form = $this->createMock(FormInterface::class);
        $formField = $this->createMock(FormInterface::class);
        $request = new Request();
        
        $this->userController
            ->method('getUser')
            ->willReturn($user);
        
        $this->userController
            ->expects($this->once())
            ->method('createForm')
            ->with(UserType::class, $user)
            ->willReturn($form);
        
        $form->method('handleRequest')->with($this->isInstanceOf(Request::class));
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('get')->with('plainPassword')->willReturn($formField);
        $formField->method('getData')->willReturn('new_password');
        
        $this->userService->expects($this->once())
            ->method('updateProfile')
            ->with($user, 'new_password');
            
        $this->userController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', $this->anything());
        
        $this->userController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_user_edit')
            ->willReturn(new RedirectResponse('/profile/edit'));
        
        $response = $this->userController->edit($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteWhenUserLoggedIn()
    {
        $user = $this->createMock(User::class);
        $request = new Request();
        $request->request = new InputBag(['_token' => 'valid_token']);
        
        $this->userController
            ->method('getUser')
            ->willReturn($user);
        
        $this->userService->expects($this->once())
            ->method('deleteAccount')
            ->with($user, 'valid_token');
            
        $this->userController
            ->expects($this->once())
            ->method('addFlash')
            ->with('success', $this->anything());
        
        $this->userController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_login')
            ->willReturn(new RedirectResponse('/login'));
        
        $response = $this->userController->delete($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteWhenExceptionThrown()
    {
        $user = $this->createMock(User::class);
        $request = new Request();
        $request->request = new InputBag(['_token' => 'invalid_token']);
        
        $this->userController
            ->method('getUser')
            ->willReturn($user);
        
        $this->userService->expects($this->once())
            ->method('deleteAccount')
            ->with($user, 'invalid_token')
            ->willThrowException(new \Exception('Invalid token'));
            
        $this->userController
            ->expects($this->once())
            ->method('addFlash')
            ->with('error', 'Invalid token');
        
        $this->userController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_user_edit')
            ->willReturn(new RedirectResponse('/profile/edit'));
        
        $response = $this->userController->delete($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
