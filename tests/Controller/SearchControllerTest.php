<?php

namespace App\Tests\Controller;

use App\Controller\SearchController;
use App\Repository\OfferRepository;
use App\Repository\ServiceRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchControllerTest extends TestCase
{
    private $serviceRepository;
    private $offerRepository;
    private $searchController;

    protected function setUp(): void
    {
        $this->serviceRepository = $this->createMock(ServiceRepository::class);
        $this->offerRepository = $this->createMock(OfferRepository::class);

        $this->searchController = $this->getMockBuilder(SearchController::class)
            ->setConstructorArgs([$this->serviceRepository, $this->offerRepository])
            ->onlyMethods(['render', 'json'])
            ->getMock();
    }

    public function testSearchOffersWithTerm()
    {
        $term = 'massage';
        $expectedOffers = [
            ['id' => 1, 'name' => 'Relaxing massage'],
            ['id' => 2, 'name' => 'Sports massage']
        ];

        $request = new Request();
        $request->query = new InputBag(['term' => $term]);

        $this->offerRepository
            ->expects($this->once())
            ->method('searchOffersByName')
            ->with($this->equalTo($term))
            ->willReturn($expectedOffers);

        $this->searchController
            ->expects($this->once())
            ->method('json')
            ->with($this->equalTo($expectedOffers))
            ->willReturn(new JsonResponse($expectedOffers));

        $response = $this->searchController->searchOffers($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchOffersWithEmptyTerm()
    {
        $term = '';
        $expectedOffers = [];

        $request = new Request();
        $request->query = new InputBag(['term' => $term]);

        $this->offerRepository
            ->expects($this->once())
            ->method('searchOffersByName')
            ->with($this->equalTo($term))
            ->willReturn($expectedOffers);

        $this->searchController
            ->expects($this->once())
            ->method('json')
            ->with($this->equalTo($expectedOffers))
            ->willReturn(new JsonResponse($expectedOffers));

        $response = $this->searchController->searchOffers($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchCitiesWithTerm()
    {
        $term = 'war';
        $expectedCities = ['Warsaw', 'Warzymice'];

        $request = new Request();
        $request->query = new InputBag(['term' => $term]);

        $this->serviceRepository
            ->expects($this->once())
            ->method('searchCities')
            ->with($this->equalTo($term))
            ->willReturn($expectedCities);

        $this->searchController
            ->expects($this->once())
            ->method('json')
            ->with($this->equalTo($expectedCities))
            ->willReturn(new JsonResponse($expectedCities));

        $response = $this->searchController->searchCities($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchCitiesWithEmptyTerm()
    {
        $term = '';
        $expectedCities = [];

        $request = new Request();
        $request->query = new InputBag(['term' => $term]);

        $this->serviceRepository
            ->expects($this->once())
            ->method('searchCities')
            ->with($this->equalTo($term))
            ->willReturn($expectedCities);

        $this->searchController
            ->expects($this->once())
            ->method('json')
            ->with($this->equalTo($expectedCities))
            ->willReturn(new JsonResponse($expectedCities));

        $response = $this->searchController->searchCities($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testResultsWithSearchTermAndCity()
    {
        $searchTerm = 'massage';
        $city = 'Warsaw';
        $expectedServices = [
            ['id' => 1, 'name' => 'Massage salon XYZ'],
            ['id' => 2, 'name' => 'Massage studio ABC']
        ];

        $request = new Request();
        $request->query = new InputBag([
            'searchTerm' => $searchTerm,
            'city' => $city
        ]);

        $this->serviceRepository
            ->expects($this->once())
            ->method('searchServicesByOfferAndCity')
            ->with(
                $this->equalTo($searchTerm),
                $this->equalTo($city)
            )
            ->willReturn($expectedServices);

        $this->searchController
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('search/results.html.twig'),
                $this->callback(function ($params) use ($searchTerm, $city, $expectedServices) {
                    return 
                        $params['searchTerm'] === $searchTerm &&
                        $params['city'] === $city &&
                        $params['services'] === $expectedServices;
                })
            )
            ->willReturn(new Response());

        $response = $this->searchController->results($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testResultsWithEmptySearchTermAndCity()
    {
        $searchTerm = '';
        $city = '';
        $expectedServices = [];

        $request = new Request();
        $request->query = new InputBag([
            'searchTerm' => $searchTerm,
            'city' => $city
        ]);

        $this->serviceRepository
            ->expects($this->once())
            ->method('searchServicesByOfferAndCity')
            ->with(
                $this->equalTo($searchTerm),
                $this->equalTo($city)
            )
            ->willReturn($expectedServices);

        $this->searchController
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('search/results.html.twig'),
                $this->callback(function ($params) use ($searchTerm, $city, $expectedServices) {
                    return 
                        $params['searchTerm'] === $searchTerm &&
                        $params['city'] === $city &&
                        $params['services'] === $expectedServices;
                })
            )
            ->willReturn(new Response());

        $response = $this->searchController->results($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testResultsWithOnlySearchTerm()
    {
        $searchTerm = 'massage';
        $city = '';
        $expectedServices = [
            ['id' => 1, 'name' => 'Massage salon XYZ'],
            ['id' => 2, 'name' => 'Massage studio ABC'],
            ['id' => 3, 'name' => 'Massage office DEF']
        ];

        $request = new Request();
        $request->query = new InputBag([
            'searchTerm' => $searchTerm,
            'city' => $city
        ]);

        $this->serviceRepository
            ->expects($this->once())
            ->method('searchServicesByOfferAndCity')
            ->with(
                $this->equalTo($searchTerm),
                $this->equalTo($city)
            )
            ->willReturn($expectedServices);

        $this->searchController
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('search/results.html.twig'),
                $this->callback(function ($params) use ($searchTerm, $city, $expectedServices) {
                    return 
                        $params['searchTerm'] === $searchTerm &&
                        $params['city'] === $city &&
                        $params['services'] === $expectedServices;
                })
            )
            ->willReturn(new Response());

        $response = $this->searchController->results($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testResultsWithOnlyCity()
    {
        $searchTerm = '';
        $city = 'Warsaw';
        $expectedServices = [
            ['id' => 1, 'name' => 'Salon XYZ'],
            ['id' => 2, 'name' => 'Studio ABC']
        ];

        $request = new Request();
        $request->query = new InputBag([
            'searchTerm' => $searchTerm,
            'city' => $city
        ]);

        $this->serviceRepository
            ->expects($this->once())
            ->method('searchServicesByOfferAndCity')
            ->with(
                $this->equalTo($searchTerm),
                $this->equalTo($city)
            )
            ->willReturn($expectedServices);

        $this->searchController
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('search/results.html.twig'),
                $this->callback(function ($params) use ($searchTerm, $city, $expectedServices) {
                    return 
                        $params['searchTerm'] === $searchTerm &&
                        $params['city'] === $city &&
                        $params['services'] === $expectedServices;
                })
            )
            ->willReturn(new Response());

        $response = $this->searchController->results($request);

        $this->assertInstanceOf(Response::class, $response);
    }
}
