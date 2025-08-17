<?php

namespace App\Tests\Controller;

use App\Constant\FlashMessages;
use App\Controller\SecurityController;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityControllerTest extends TestCase
{
    private $authenticationUtils;
    private $passwordHasher;
    private $entityManager;
    private $validator;
    private $securityController;

    protected function setUp(): void
    {
        $this->authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->securityController = $this->getMockBuilder(SecurityController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'createForm', 'render', 'redirectToRoute'])
            ->getMock();
    }

    public function testLoginWhenUserIsAuthenticated()
    {
        $this->securityController
            ->method('getUser')
            ->willReturn(new User());
        
        $this->securityController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_home')
            ->willReturn(new RedirectResponse('/'));
        
        $response = $this->securityController->login($this->authenticationUtils);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testLoginWhenUserIsNotAuthenticated()
    {
        $this->securityController
            ->method('getUser')
            ->willReturn(null);
        
        $this->authenticationUtils
            ->method('getLastAuthenticationError')
            ->willReturn(null);
        
        $this->authenticationUtils
            ->method('getLastUsername')
            ->willReturn('testuser');
        
        $this->securityController
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('security/login.html.twig'),
                $this->callback(function ($params) {
                    return array_key_exists('last_username', $params) &&
                           array_key_exists('error', $params) &&
                           $params['last_username'] === 'testuser' &&
                           $params['error'] === null;
                })
            )
            ->willReturn(new Response());
        
        $response = $this->securityController->login($this->authenticationUtils);
        
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRegisterWhenUserIsAuthenticated()
    {
        $this->securityController
            ->method('getUser')
            ->willReturn(new User());
        
        $this->securityController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_home')
            ->willReturn(new RedirectResponse('/'));
        
        $request = new Request();
        $response = $this->securityController->register(
            $request, 
            $this->passwordHasher, 
            $this->entityManager, 
            $this->validator
        );
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testRegisterWhenFormSubmittedAndValid()
    {
        $this->securityController
            ->method('getUser')
            ->willReturn(null);
        
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $plainPassword = 'secret123';
        $hashedPassword = 'hashed_password';
        
        $plainPasswordField = $this->createMock(FormInterface::class);
        $plainPasswordField
            ->method('getData')
            ->willReturn($plainPassword);
        
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('get')
            ->with('plainPassword')
            ->willReturn($plainPasswordField);
        
        $this->securityController
            ->method('createForm')
            ->with(RegistrationFormType::class, $this->isInstanceOf(User::class))
            ->willReturn($form);
        
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), $plainPassword)
            ->willReturn($hashedPassword);
        
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));
        
        $this->entityManager
            ->expects($this->once())
            ->method('flush');
        
        $this->securityController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_login')
            ->willReturn(new RedirectResponse('/login'));
        
        $response = $this->securityController->register(
            $request, 
            $this->passwordHasher, 
            $this->entityManager, 
            $this->validator
        );
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testRegisterWhenFormNotSubmitted()
    {
        $this->securityController
            ->method('getUser')
            ->willReturn(null);
        
        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(FormView::class);
        $request = new Request();
        
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);
        
        $this->securityController
            ->method('createForm')
            ->with(RegistrationFormType::class, $this->isInstanceOf(User::class))
            ->willReturn($form);
        
        $this->securityController
            ->expects($this->once())
            ->method('render')
            ->with(
                'security/register.html.twig',
                $this->callback(function ($params) {
                    return isset($params['registrationForm']);
                })
            )
            ->willReturn(new Response());
        
        $response = $this->securityController->register(
            $request, 
            $this->passwordHasher, 
            $this->entityManager, 
            $this->validator
        );
        
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRegisterWhenFormSubmittedButInvalid()
    {
        $this->securityController
            ->method('getUser')
            ->willReturn(null);
        
        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(FormView::class);
        $request = new Request();
        
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($formView);
        
        $this->securityController
            ->method('createForm')
            ->with(RegistrationFormType::class, $this->isInstanceOf(User::class))
            ->willReturn($form);
        
        $this->securityController
            ->expects($this->once())
            ->method('render')
            ->with(
                'security/register.html.twig',
                $this->callback(function ($params) {
                    return isset($params['registrationForm']);
                })
            )
            ->willReturn(new Response());
        
        $response = $this->securityController->register(
            $request, 
            $this->passwordHasher, 
            $this->entityManager, 
            $this->validator
        );
        
        $this->assertInstanceOf(Response::class, $response);
    }
}
