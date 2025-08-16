<?php

namespace App\DataFixtures;

use App\Entity\Opinion;
use App\Entity\Booking;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class OpinionFixtures extends Fixture implements DependentFixtureInterface
{
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create('pl_PL');
    }

    public function load(ObjectManager $manager): void
    {
        if (!$manager->getRepository(Opinion::class)->findOneBy([])) {
            $bookings = $manager->getRepository(Booking::class)
                ->createQueryBuilder('b')
                ->where('b.startTime < :now')
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->getResult();

            foreach ($bookings as $booking) {
                try {
                    if ($this->faker->boolean(70)) {
                        $opinion = new Opinion();
                        $opinion->setRating($this->generateRandomRating())
                            ->setContent($this->faker->sentences($this->faker->numberBetween(1, 15), true))
                            ->setBooking($booking)
                            ->setService($booking->getService());

                        $manager->persist($opinion);
                    }
                } catch (\Exception $e) {
                    echo sprintf("Error creating opinion for booking %d: %s\n",
                        $booking->getId(),
                        $e->getMessage()
                    );
                }
            }

            try {
                $manager->flush();
            } catch (\Exception $e) {
                echo "Error during final flush: " . $e->getMessage() . "\n";
            }
        }
    }

    private function generateRandomRating(): int
    {
        return $this->faker->numberBetween(1, 5);
    }

    public function getDependencies(): array
    {
        return [
            BookingFixtures::class,
        ];
    }
}