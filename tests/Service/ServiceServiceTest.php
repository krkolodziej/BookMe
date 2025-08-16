<?php

namespace App\Tests\Service;

use App\Entity\Opinion;
use App\Entity\Service;
use App\Repository\ServiceImageRepository;
use App\Repository\ServiceRepository;
use App\Service\ServiceService;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class ServiceServiceTest extends TestCase
{
    private $serviceRepositoryMock;
    private $serviceImageRepositoryMock;
    private $serviceService;

    protected function setUp(): void
    {
        // Tworzenie mocków dla repozytoriów
        $this->serviceRepositoryMock = $this->createMock(ServiceRepository::class);
        $this->serviceImageRepositoryMock = $this->createMock(ServiceImageRepository::class);
        
        // Inicjalizacja serwisu z mockami
        $this->serviceService = new ServiceService(
            $this->serviceRepositoryMock,
            $this->serviceImageRepositoryMock
        );
    }

    /**
     * Test metody getServiceByEncodedName
     */
    public function testGetServiceByEncodedName(): void
    {
        // Przygotowanie
        $encodedName = 'test-service';
        $serviceMock = $this->createMock(Service::class);
        
        // Konfiguracja mocka repozytorium
        $this->serviceRepositoryMock
            ->expects($this->once())
            ->method('findByEncodedNameWithDetails')
            ->with($encodedName)
            ->willReturn($serviceMock);
        
        // Wykonanie
        $result = $this->serviceService->getServiceByEncodedName($encodedName);
        
        // Weryfikacja
        $this->assertSame($serviceMock, $result);
    }

    /**
     * Test metody getServiceImages
     */
    public function testGetServiceImages(): void
    {
        // Przygotowanie
        $serviceId = 123;
        $expectedImages = ['image1.jpg', 'image2.jpg'];
        
        // Konfiguracja mocka repozytorium
        $this->serviceImageRepositoryMock
            ->expects($this->once())
            ->method('findByServiceId')
            ->with($serviceId)
            ->willReturn($expectedImages);
        
        // Wykonanie
        $result = $this->serviceService->getServiceImages($serviceId);
        
        // Weryfikacja
        $this->assertSame($expectedImages, $result);
    }

    /**
     * Test metody calculateAverageRating gdy są opinie
     */
    public function testCalculateAverageRatingWithOpinions(): void
    {
        // Przygotowanie
        $serviceMock = $this->createMock(Service::class);
        
        // Tworzenie opinii z ocenami
        $opinion1 = $this->createMock(Opinion::class);
        $opinion1->expects($this->once())
            ->method('getRating')
            ->willReturn(4);
            
        $opinion2 = $this->createMock(Opinion::class);
        $opinion2->expects($this->once())
            ->method('getRating')
            ->willReturn(5);
            
        $opinion3 = $this->createMock(Opinion::class);
        $opinion3->expects($this->once())
            ->method('getRating')
            ->willReturn(3);
        
        // Kolekcja opinii
        $opinionsCollection = new ArrayCollection([$opinion1, $opinion2, $opinion3]);
        
        // Konfiguracja mocka serwisu
        $serviceMock->expects($this->atLeastOnce())
            ->method('getOpinions')
            ->willReturn($opinionsCollection);
        
        // Wykonanie
        $result = $this->serviceService->calculateAverageRating($serviceMock);
        
        // Weryfikacja
        $expectedAverage = round((4 + 5 + 3) / 3, 1); // 4.0
        $this->assertEquals($expectedAverage, $result['averageRating']);
        $this->assertEquals(3, $result['opinionsCount']);
    }

    /**
     * Test metody calculateAverageRating gdy nie ma opinii
     */
    public function testCalculateAverageRatingWithoutOpinions(): void
    {
        // Przygotowanie
        $serviceMock = $this->createMock(Service::class);
        $emptyCollection = new ArrayCollection([]);
        
        // Konfiguracja mocka serwisu
        $serviceMock->expects($this->atLeastOnce())
            ->method('getOpinions')
            ->willReturn($emptyCollection);
        
        // Wykonanie
        $result = $this->serviceService->calculateAverageRating($serviceMock);
        
        // Weryfikacja
        $this->assertEquals(0.0, $result['averageRating']);
        $this->assertEquals(0, $result['opinionsCount']);
    }
} 