<?php

namespace App\Tests\Controller;

use App\Controller\ServiceController;
use App\Entity\Service;
use App\Service\ServiceService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ServiceControllerTest extends TestCase
{
    private $serviceServiceMock;
    private $serviceController;
    
    protected function setUp(): void
    {
        // Tworzenie mocka dla ServiceService
        $this->serviceServiceMock = $this->createMock(ServiceService::class);
        $securityMock = $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class);
        
        // Dodanie metody renderowania dla kontrolera
        $this->serviceController = $this->getMockBuilder(ServiceController::class)
            ->setConstructorArgs([$this->serviceServiceMock, $securityMock])
            ->onlyMethods(['render', 'redirectToRoute'])
            ->getMock();
    }
    
    /**
     * Test metody show gdy serwis nie istnieje
     */
    public function testShowWhenServiceNotFound(): void
    {
        // Przygotowanie
        $encodedName = 'non-existent-service';
        
        // Konfiguracja mocka serwisu (zwraca null)
        $this->serviceServiceMock
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willReturn(null);
        
        // Konfiguracja mocka przekierowania
        $redirectResponse = $this->createMock(RedirectResponse::class);
        $this->serviceController
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('home')
            ->willReturn($redirectResponse);
        
        // Wykonanie
        $response = $this->serviceController->show($encodedName);
        
        // Weryfikacja
        $this->assertSame($redirectResponse, $response);
    }
    
    /**
     * Test metody show gdy serwis istnieje
     */
    public function testShowWhenServiceExists(): void
    {
        // Przygotowanie
        $encodedName = 'existing-service';
        $serviceId = 123;
        $serviceMock = $this->createMock(Service::class);
        
        // Konfiguracja mocka serwisu dla getId
        $serviceMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($serviceId);
        
        // Konfiguracja mocka serwisu (zwraca serwis)
        $this->serviceServiceMock
            ->expects($this->once())
            ->method('getServiceByEncodedName')
            ->with($encodedName)
            ->willReturn($serviceMock);
        
        // Konfiguracja mocka dla obliczania oceny
        $ratingData = [
            'averageRating' => 4.5,
            'opinionsCount' => 10
        ];
        $this->serviceServiceMock
            ->expects($this->once())
            ->method('calculateAverageRating')
            ->with($serviceMock)
            ->willReturn($ratingData);
        
        // Konfiguracja mocka dla pobierania zdjęć
        $serviceImages = ['image1.jpg', 'image2.jpg'];
        $this->serviceServiceMock
            ->expects($this->once())
            ->method('getServiceImages')
            ->with($serviceId)
            ->willReturn($serviceImages);
        
        // Konfiguracja mocka renderowania
        $viewResponse = $this->createMock(Response::class);
        $this->serviceController
            ->expects($this->once())
            ->method('render')
            ->with(
                'service/show.html.twig',
                [
                    'service' => $serviceMock,
                    'averageRating' => $ratingData['averageRating'],
                    'opinionsCount' => $ratingData['opinionsCount'],
                    'serviceImages' => $serviceImages,
                    'isEmployee' => false,
                ]
            )
            ->willReturn($viewResponse);
        
        // Wykonanie
        $response = $this->serviceController->show($encodedName);
        
        // Weryfikacja
        $this->assertSame($viewResponse, $response);
    }
} 