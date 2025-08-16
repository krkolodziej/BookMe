<?php

namespace App\Service;

use App\Entity\ServiceCategory;
use App\Repository\ServiceCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminServiceCategoryService
{
    public function __construct(
        private readonly ServiceCategoryRepository $serviceCategoryRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     *
     * @return array
     */
    public function getAllCategories(): array
    {
        return $this->serviceCategoryRepository->findAll();
    }

    /**
     *
     * @param string $searchTerm
     * @return array
     */
    public function searchCategoriesByName(string $searchTerm): array
    {
        return $this->serviceCategoryRepository->searchCategoriesByName($searchTerm);
    }

    /**
     *
     * @param string $encodedName
     * @return ServiceCategory
     * @throws NotFoundHttpException
     */
    public function getCategoryByEncodedName(string $encodedName): ServiceCategory
    {
        $category = $this->serviceCategoryRepository->findOneBy(['encodedName' => $encodedName]);

        if (!$category) {
            throw new NotFoundHttpException('Kategoria nie została znaleziona.');
        }

        return $category;
    }

    /**
     *
     * @param ServiceCategory $category
     * @param bool $flush
     * @return void
     */
    public function saveCategory(ServiceCategory $category, bool $flush = true): void
    {
        $this->entityManager->persist($category);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     *
     * @param ServiceCategory $category
     * @param bool $flush
     * @return void
     */
    public function removeCategory(ServiceCategory $category, bool $flush = true): void
    {
        $this->entityManager->remove($category);
        if ($flush) {
            $this->entityManager->flush();
        }
    }
} 