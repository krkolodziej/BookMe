<?php

namespace App\DataFixtures;

use App\Entity\Employee;
use App\Entity\User;
use App\Entity\Booking;
use App\Entity\Notification;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class BookingFixtures extends Fixture implements DependentFixtureInterface
{
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create('pl_PL');
    }

    public function load(ObjectManager $manager): void
    {
        if (!$manager->getRepository(Booking::class)->findOneBy([])) {
            $employees = $manager->getRepository(Employee::class)->findAll();

            $employeesByService = [];
            foreach ($employees as $employee) {
                $serviceId = $employee->getService()->getId();
                if (!isset($employeesByService[$serviceId])) {
                    $employeesByService[$serviceId] = [];
                }
                $employeesByService[$serviceId][] = $employee;
            }

            $services = $manager->getRepository(Service::class)
                ->createQueryBuilder('s')
                ->leftJoin('s.offers', 'o')
                ->having('COUNT(o) > 0')
                ->groupBy('s.id')
                ->getQuery()
                ->getResult();

            $users = $manager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.userType = :type')
                ->setParameter('type', 'customer')
                ->getQuery()
                ->getResult();

            foreach ($services as $service) {
                if (!isset($employeesByService[$service->getId()])) {
                    continue;
                }

                $serviceEmployees = $employeesByService[$service->getId()];
                $offers = $service->getOffers()->toArray();

                if (empty($serviceEmployees) || empty($offers)) {
                    continue;
                }

                $numberOfBookings = random_int(2, 5);

                for ($i = 0; $i < $numberOfBookings; $i++) {
                    try {
                        $startTime = $this->faker->dateTimeBetween('-1 year', '+1 month');
                        $user = $this->faker->randomElement($users);
                        $offer = $this->faker->randomElement($offers);
                        $employee = $this->faker->randomElement($serviceEmployees);

                        if (!$employee || !$employee->getId() || !$employee->getUser()) {
                            continue;
                        }

                        $booking = new Booking();
                        $booking->setUser($user)
                            ->setStartTime($startTime)
                            ->setOffer($offer)
                            ->setService($service)
                            ->setEmployee($employee);

                        $booking->setEndTime();

                        $manager->persist($booking);

                        if ($startTime > new \DateTime()) {
                            $notification = new Notification();
                            $notification->setMessage(sprintf(
                                'Nowa wizyta od %s %s zaplanowana na %s',
                                $user->getFirstName(),
                                $user->getLastName(),
                                $startTime->format('Y-m-d H:i')
                            ))
                                ->setEmployee($employee)
                                ->setIsRead(false);

                            $manager->persist($notification);
                        }
                    } catch (\Exception $e) {
                        echo sprintf(
                            "Error creating booking for service %d: %s\n",
                            $service->getId(),
                            $e->getMessage()
                        );
                        continue;
                    }
                }
            }

            try {
                $manager->flush();
            } catch (\Exception $e) {
                echo "Error during final flush: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            EmployeeFixtures::class,
            ServiceFixtures::class,
            OfferFixtures::class
        ];
    }
}