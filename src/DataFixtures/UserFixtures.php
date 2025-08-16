<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;
use Symfony\Component\HttpClient\HttpClient;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $adminUser = new User();
        $adminUser->setEmail('admin@admin.com')
            ->setFirstName('Admin')
            ->setLastName('User')
            ->setGender('male')
            ->setIsAdmin(true)
            ->setAvatarUrl($this->getAvatarUrl('male'));

        $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'zaq1@WSX');
        $adminUser->setPassword($hashedPassword);

        $manager->persist($adminUser);

        for ($i = 0; $i < 50; $i++) {
            $gender = $faker->randomElement(['male', 'female']);
            $firstName = $gender === 'male' ? $faker->firstNameMale : $faker->firstNameFemale;
            $lastName = $faker->lastName;

            $user = new User();
            $user->setEmail($faker->email)
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setGender($gender)
                ->setIsAdmin(false)
                ->setAvatarUrl($this->getAvatarUrl($gender));

            $hashedPassword = $this->passwordHasher->hashPassword($user, 'zaq1@WSX');
            $user->setPassword($hashedPassword);

            $manager->persist($user);
        }

        $manager->flush();
    }

    private function getAvatarUrl(string $gender): string
    {
        $client = HttpClient::create();
        $response = $client->request('GET', "https://randomuser.me/api/?gender={$gender}");

        $data = $response->toArray();
        return $data['results'][0]['picture']['large'];
    }
}