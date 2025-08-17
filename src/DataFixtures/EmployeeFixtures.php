<?php

namespace App\DataFixtures;

use App\Entity\Employee;
use App\Entity\User;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;
use Symfony\Component\HttpClient\HttpClient;

class EmployeeFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;
    private array $avatarCache = [
        'male' => [],
        'female' => []
    ];

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('pl_PL');
        $defaultPassword = 'zaq1@WSX';

        // Get all services directly from repository to avoid proxy issues
        $services = $manager->getRepository(Service::class)->findAll();
        
        if (empty($services)) {
            return; // No services to create employees for
        }
        
        foreach ($services as $service) {
            for ($i = 0; $i < 1; $i++) {
                $gender = $faker->randomElement(['male', 'female']);
                $firstName = $gender === 'male' ? $faker->firstNameMale : $faker->firstNameFemale;
                $lastName = $faker->lastName;
                $email = $faker->unique()->safeEmail;

                $user = new User();
                $user->setEmail($email)
                    ->setFirstName($firstName)
                    ->setLastName($lastName)
                    ->setGender($gender)
                    ->setIsAdmin(false)
                    ->setUserType('employee')
                    ->setPassword($this->passwordHasher->hashPassword($user, $defaultPassword));

                if (method_exists($user, 'setAvatarUrl')) {
                    $user->setAvatarUrl($this->getNextAvatar($gender));
                }

                $manager->persist($user);

                $employee = new Employee();
                $employee->setUser($user);
                $employee->setService($service);

                $manager->persist($employee);
            }
        }

        $manager->flush();
    }

    private function getNextAvatar(string $gender): string
    {
        if (empty($this->avatarCache[$gender])) {
            $this->preloadAvatars(10, $gender);
        }
        return array_shift($this->avatarCache[$gender]) ?? 'default-avatar.jpg';
    }

    private function preloadAvatars(int $count, string $gender): void
    {
        try {
            $client = HttpClient::create();
            $response = $client->request('GET', "https://randomuser.me/api/?gender={$gender}&results={$count}");
            $data = $response->toArray();
            foreach ($data['results'] as $result) {
                $this->avatarCache[$gender][] = $result['picture']['large'];
            }
        } catch (\Exception $e) {
            // Fallback to placeholder avatars if API fails
            for ($i = 0; $i < $count; $i++) {
                $this->avatarCache[$gender][] = "https://ui-avatars.com/api/?name=Employee&background=random&color=fff&size=200";
            }
        }
    }

    public function getDependencies(): array
    {
        return [
            ServiceFixtures::class
        ];
    }
}