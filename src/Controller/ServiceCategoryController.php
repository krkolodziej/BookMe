<?php

namespace App\Controller;

use App\Repository\ServiceCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ServiceCategoryController extends AbstractController
{
    public function __construct(
        private readonly ServiceCategoryRepository $serviceCategoryRepository
    ){}

    #[Route('/kategorie/{encodedName}', name: 'category_details')]
    public function show(string $encodedName, Request $request): Response
    {
        $searchTerm = $request->query->get('searchTerm', '');

        $category = $this->serviceCategoryRepository->getByEncodedName($encodedName, $searchTerm);

        if (!$category) {
            throw $this->createNotFoundException('Kategoria nie została znaleziona.');
        }

        return $this->render('service_category/show.html.twig', [
            'category' => $category,
            'searchTerm' => $searchTerm
        ]);
    }
}