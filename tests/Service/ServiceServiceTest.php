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
        $this->serviceRepositoryMock = $this->createMock(ServiceRepository::class);
        $this->serviceImageRepositoryMock = $this->createMock(ServiceImageRepository::class);
        
        $this->serviceService = new ServiceService(
            $this->serviceRepositoryMock,
            $this->serviceImageRepositoryMock
        );
    }

    
    public function testGetServiceByEncodedName(): void
    {
        $encodedName = 'test-service';
        $serviceMock = $this->createMock(Service::class);
        
        $this->serviceRepositoryMock
            ->expects($this->once())
            ->method('findByEncodedNameWithDetails')
            ->with($encodedName)
            ->willReturn($serviceMock);
        
        $result = $this->serviceService->getServiceByEncodedName($encodedName);
        
        $this->assertSame($serviceMock, $result);
    }

    
    public function testGetServiceImages(): void
    {
        $serviceId = 123;
        $expectedImages = ['image1.jpg', 'image2.jpg'];
        
        $this->serviceImageRepositoryMock
            ->expects($this->once())
            ->method('findByServiceId')
            ->with($serviceId)
            ->willReturn($expectedImages);
        
        $result = $this->serviceService->getServiceImages($serviceId);
        
        $this->assertSame($expectedImages, $result);
    }

    
    public function testCalculateAverageRatingWithOpinions(): void
    {
        $serviceMock = $this->createMock(Service::class);
        
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
        
        $opinionsCollection = new ArrayCollection([$opinion1, $opinion2, $opinion3]);
        
        $serviceMock->expects($this->atLeastOnce())
            ->method('getOpinions')
            ->willReturn($opinionsCollection);
        
        $result = $this->serviceService->calculateAverageRating($serviceMock);
        
        $expectedAverage = round((4 + 5 + 3) / 3, 1); // 4.0
        $this->assertEquals($expectedAverage, $result['averageRating']);
        $this->assertEquals(3, $result['opinionsCount']);
    }

    
    public function testCalculateAverageRatingWithoutOpinions(): void
    {
        $serviceMock = $this->createMock(Service::class);
        $emptyCollection = new ArrayCollection([]);
        
        $serviceMock->expects($this->atLeastOnce())
            ->method('getOpinions')
            ->willReturn($emptyCollection);
        
        $result = $this->serviceService->calculateAverageRating($serviceMock);
        
        $this->assertEquals(0.0, $result['averageRating']);
        $this->assertEquals(0, $result['opinionsCount']);
    }
} 