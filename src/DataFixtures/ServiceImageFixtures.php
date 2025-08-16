<?php

namespace App\DataFixtures;

use App\Entity\ServiceImage;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ServiceImageFixtures extends Fixture implements DependentFixtureInterface
{
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create('pl_PL');
    }

    public function load(ObjectManager $manager): void
    {
        if (!$manager->getRepository(ServiceImage::class)->findOneBy([])) {
            $services = $manager->getRepository(Service::class)->findAll();

            foreach ($services as $service) {
                $imagesCount = random_int(4, 8);

                for ($i = 0; $i < $imagesCount; $i++) {
                    $image = new ServiceImage();
                    $image->setUrl(sprintf('https://picsum.photos/seed/%s/1200/800', uniqid()))
                        ->setService($service);

                    $manager->persist($image);
                }
            }

            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [
            ServiceFixtures::class,
        ];
    }
}