<?php

namespace App\Twig;

use App\Repository\ServiceCategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CategoryExtension extends AbstractExtension
{
    private const ADDITIONAL_CATEGORIES = [
        'OTHER' => [
            'name' => 'Inne',
            'imageUrl' => 'https://i.gyazo.com/a60c5545bc9c8f2b9599cd02949767b8.png',
            'encodedName' => 'inne'
        ]
    ];

    public function __construct(
        private ServiceCategoryRepository $serviceCategoryRepository
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_service_categories', [$this, 'getServiceCategories']),
        ];
    }

    public function getServiceCategories(): array
    {
        $categories = $this->serviceCategoryRepository->findAll();
        foreach (self::ADDITIONAL_CATEGORIES as $category) {
            $categories[] = (object)$category;
        }

        return $categories;
    }
}