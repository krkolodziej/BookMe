<?php

namespace App\DataFixtures;

use App\Entity\OpeningHour;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OpeningHoursFixtures extends Fixture implements DependentFixtureInterface
{
    private array $polishDaysOfWeek = [
        'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek',
        'Piątek', 'Sobota', 'Niedziela'
    ];

    public function load(ObjectManager $manager): void
    {
        if (!$manager->getRepository(OpeningHour::class)->findOneBy([])) {
            $services = $manager->getRepository(Service::class)->findAll();

            foreach ($services as $service) {
                foreach ($this->generateOpeningHours() as $openingHour) {
                    $openingHour->setService($service);
                    $manager->persist($openingHour);
                }
            }

            $manager->flush();
        }
    }

    private function generateOpeningHours(): array
    {
        $openingHours = [];

        foreach ($this->polishDaysOfWeek as $day) {
            $isClosed = random_int(0, 4) === 0;

            $openingHour = new OpeningHour();
            $openingHour->setDayOfWeek($day);

            if ($isClosed) {
                $openingHour
                    ->setOpeningTime(new \DateTime('00:00'))
                    ->setClosingTime(new \DateTime('00:00'))
                    ->setClosed(true);
            } else {
                $openingTime = random_int(6, 9);
                $closingTime = random_int(16, 19);

                $openingHour
                    ->setOpeningTime(new \DateTime($openingTime . ':00'))
                    ->setClosingTime(new \DateTime($closingTime . ':00'))
                    ->setClosed(false);
            }

            $openingHours[] = $openingHour;
        }

        return $openingHours;
    }

    public function getDependencies(): array
    {
        return [
            ServiceFixtures::class,
        ];
    }
}