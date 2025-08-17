<?php

namespace App\DataFixtures;

use App\Entity\Service;
use App\Entity\ServiceCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ServiceFixtures extends Fixture implements DependentFixtureInterface
{
    private array $usedNames = [];
    private ObjectManager $manager;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $faker = Factory::create('pl_PL');

        // Check if services already exist to avoid duplicates
        $existingServices = $manager->getRepository(Service::class)->findAll();
        if (!empty($existingServices)) {
            return; // Skip if services already exist
        }

        $categories = $manager->getRepository(ServiceCategory::class)->findAll();

        if (empty($categories)) {
            throw new \RuntimeException('Nie znaleziono żadnych kategorii. Upewnij się, że ServiceCategoryFixtures zostały wykonane pierwsze.');
        }

        for ($i = 0; $i < 200; $i++) {
            $service = new Service();
            $this->populateService($service, $faker);
            $randomCategory = $categories[array_rand($categories)];
            $service->setServiceCategory($randomCategory);
            $manager->persist($service);
        }

        for ($i = 0; $i < 100; $i++) {
            $service = new Service();
            $this->populateService($service, $faker);
            $service->setServiceCategory(null); 
            $manager->persist($service);
        }

        $manager->flush();
    }

    private function populateService(Service $service, \Faker\Generator $faker): void
    {
        $name = $this->generateUniqueName($faker);
        $service->setName($name);

        $service->setDescription($faker->sentence(30));

        $service->setCity($faker->city);
        $service->setStreet($faker->streetAddress);
        $service->setPostalCode(sprintf('%02d-%03d', rand(0,99), rand(0,999)));
        $service->setPhoneNumber(sprintf('+48 %03d-%03d-%03d', rand(100,999), rand(100,999), rand(100,999)));

        $service->setImageUrl(sprintf('https://picsum.photos/seed/%s/1200/800', uniqid()));
        $service->setOpinionsCount(0);
        $service->setAverageRating(0);
    }

    private function generateUniqueName(\Faker\Generator $faker): string
    {
        $types = ['Salon', 'Studio', 'Gabinet', 'Klinika', 'Centrum', 'Pracownia'];
        $adjectives = ['Nowoczesny', 'Elegancki', 'Profesjonalny', 'Ekskluzywny', 'Przyjazny', 'Komfortowy'];
        
        $type = $faker->randomElement($types);
        $adjective = $faker->randomElement($adjectives);
        $counter = count($this->usedNames) + 1;
        
        return sprintf('%s %s #%d', $adjective, $type, $counter);
    }

    public function getDependencies(): array
    {
        return [
            ServiceCategoryFixtures::class,
        ];
    }
}