<?php

namespace App\Controller;

use App\Repository\OfferRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/search')]
class SearchController extends AbstractController
{
    private ServiceRepository $serviceRepository;
    private OfferRepository $offerRepository;

    public function __construct(ServiceRepository $serviceRepository, OfferRepository $offerRepository)
    {
        $this->serviceRepository = $serviceRepository;
        $this->offerRepository = $offerRepository;
    }

    #[Route('/offers', name: 'search_offers', methods: ['GET'])]
    public function searchOffers(Request $request): JsonResponse
    {
        $term = $request->query->get('term', '');
        $offers = $this->offerRepository->searchOffersByName($term);

        return $this->json($offers);
    }

    #[Route('/cities', name: 'search_cities', methods: ['GET'])]
    public function searchCities(Request $request): JsonResponse
    {
        $term = $request->query->get('term', '');
        $cities = $this->serviceRepository->searchCities($term);

        return $this->json($cities);
    }

    #[Route('/results', name: 'search_results', methods: ['GET'])]
    public function results(Request $request): Response
    {
        $searchTerm = $request->query->get('searchTerm', '');
        $city = $request->query->get('city', '');

        $services = $this->serviceRepository->searchServicesByOfferAndCity($searchTerm, $city);

        return $this->render('search/results.html.twig', [
            'searchTerm' => $searchTerm,
            'city' => $city,
            'services' => $services,
        ]);
    }
}