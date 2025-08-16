<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\AdminController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AdminControllerTest extends TestCase
{
    private $adminController;

    protected function setUp(): void
    {
        $serviceRepository = $this->createMock(\App\Repository\ServiceRepository::class);
        $serviceCategoryRepository = $this->createMock(\App\Repository\ServiceCategoryRepository::class);
        $employeeRepository = $this->createMock(\App\Repository\EmployeeRepository::class);
        $userRepository = $this->createMock(\App\Repository\UserRepository::class);

        $this->adminController = $this->getMockBuilder(AdminController::class)
            ->setConstructorArgs([$serviceRepository, $serviceCategoryRepository, $employeeRepository, $userRepository])
            ->onlyMethods(['render'])
            ->getMock();
    }

    public function testIndex()
    {
        // Konfiguracja mocka render
        $this->adminController
            ->expects($this->once())
            ->method('render')
            ->with('admin/index.html.twig')
            ->willReturn(new Response());

        // Wywołanie metody kontrolera
        $response = $this->adminController->index();

        // Weryfikacja odpowiedzi
        $this->assertInstanceOf(Response::class, $response);
    }
} 