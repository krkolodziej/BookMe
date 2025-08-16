<?php

namespace App\DataFixtures;

use App\Entity\Offer;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class OfferFixtures extends Fixture implements DependentFixtureInterface
{
    private array $categoryToOfferNames = [
        'Fryzjer' => ['Strzyżenie męskie', 'Strzyżenie damskie', 'Farbowanie włosów', 'Modelowanie włosów'],
        'Barber Shop' => ['Strzyżenie brody', 'Strzyżenie włosów', 'Pakiet: broda + włosy', 'Odżywianie brody'],
        'Trening i dieta' => ['Konsultacja dietetyczna', 'Plan treningowy', 'Trening personalny', 'Analiza składu ciała'],
        'Masaż' => ['Masaż relaksacyjny', 'Masaż leczniczy', 'Masaż sportowy', 'Masaż gorącymi kamieniami'],
        'Fizjoterapia' => ['Rehabilitacja kręgosłupa', 'Terapia manualna', 'Trening rehabilitacyjny', 'Kinesiotaping'],
        'Salon Kosmetyczny' => ['Peeling kawitacyjny', 'Mezoterapia igłowa', 'Mikrodermabrazja', 'Zabieg nawilżający'],
        'Tatuaż i Piercing' => ['Tatuaż mały', 'Tatuaż średni', 'Piercing nosa', 'Piercing ucha'],
        'Stomatolog' => ['Wypełnienie zęba', 'Czyszczenie kamienia', 'Implant zębowy', 'Leczenie kanałowe'],
        'Medycyna estetyczna' => ['Botox', 'Wypełnienie kwasem hialuronowym', 'Zabieg laserowy', 'Lifting twarzy'],
        'Paznokcie' => ['Manicure hybrydowy', 'Pedicure SPA', 'Przedłużanie paznokci', 'Zdobienie paznokci'],
        'Brwi i rzęsy' => ['Henna brwi', 'Przedłużanie rzęs', 'Laminacja rzęs', 'Regulacja brwi'],
        'Makijaż' => ['Makijaż ślubny', 'Makijaż wieczorowy', 'Makijaż dzienny', 'Kurs makijażu'],
        'Depilacja' => ['Depilacja woskiem', 'Depilacja laserowa', 'Depilacja cukrowa', 'Depilacja twarzy'],
        'Inne' => ['Konsultacja ogólna', 'Usługa specjalna', 'Dostosowana usługa', 'Porada ekspertów']
    ];

    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create('pl_PL');
    }

    public function load(ObjectManager $manager): void
    {
        if (!$manager->getRepository(Offer::class)->findOneBy([])) {
            $services = $manager->getRepository(Service::class)
                ->createQueryBuilder('s')
                ->leftJoin('s.serviceCategory', 'sc')
                ->addSelect('sc')
                ->getQuery()
                ->getResult();

            foreach ($services as $service) {
                $categoryName = $service->getServiceCategory()?->getName() ?? 'Inne';
                $offerNames = $this->categoryToOfferNames[$categoryName] ?? $this->categoryToOfferNames['Inne'];

                $usedOfferNames = [];
                $numberOfOffers = random_int(3, min(count($offerNames), 6));

                for ($i = 0; $i < $numberOfOffers; $i++) {
                    $availableNames = array_diff($offerNames, $usedOfferNames);
                    if (empty($availableNames)) {
                        break;
                    }

                    $name = $this->faker->randomElement($availableNames);
                    $usedOfferNames[] = $name;

                    $offer = new Offer();
                    $offer->setName($name)
                        ->setDuration($this->getRandomNumberDivisibleBy10(20, 90))
                        ->setPrice((string)$this->getRandomNumberDivisibleBy10(20, 300))
                        ->setService($service);

                    $manager->persist($offer);
                }
            }

            $manager->flush();
        }
    }

    private function getRandomNumberDivisibleBy10(int $min, int $max): int
    {
        return floor($this->faker->numberBetween($min, $max) / 10) * 10;
    }

    public function getDependencies(): array
    {
        return [
            ServiceFixtures::class
        ];
    }
}