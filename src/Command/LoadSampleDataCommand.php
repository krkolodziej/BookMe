<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Employee;
use App\Entity\ServiceCategory;
use App\Entity\Service;
use App\Entity\OpeningHours;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-sample-data',
    description: 'Load sample data for production environment',
)]
class LoadSampleDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Loading sample data...');

        // Create sample users
        $user = new User();
        $user->setEmail('admin@bookme.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'admin123'));
        $this->entityManager->persist($user);

        $client = new User();
        $client->setEmail('client@example.com');
        $client->setRoles(['ROLE_USER']);
        $client->setPassword($this->passwordHasher->hashPassword($client, 'client123'));
        $this->entityManager->persist($client);

        // Create sample employee
        $employee = new Employee();
        $employee->setName('Jan Kowalski');
        $employee->setEmail('jan@bookme.com');
        $employee->setPhone('123456789');
        $employee->setPosition('Fryzjer');
        $this->entityManager->persist($employee);

        // Create service category
        $category = new ServiceCategory();
        $category->setName('Fryzjerstwo');
        $category->setDescription('Usługi fryzjerskie');
        $this->entityManager->persist($category);

        $this->entityManager->flush();

        // Create sample service
        $service = new Service();
        $service->setName('Strzyżenie męskie');
        $service->setDescription('Profesjonalne strzyżenie męskie');
        $service->setPrice(50.00);
        $service->setDuration(30);
        $service->setCategory($category);
        $this->entityManager->persist($service);

        // Create opening hours
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($days as $day) {
            $hours = new OpeningHours();
            $hours->setDayOfWeek($day);
            $hours->setOpenTime(new \DateTime('09:00'));
            $hours->setCloseTime(new \DateTime('17:00'));
            $this->entityManager->persist($hours);
        }

        $this->entityManager->flush();

        $output->writeln('Sample data loaded successfully!');
        $output->writeln('Login credentials:');
        $output->writeln('Admin: admin@bookme.com / admin123');
        $output->writeln('Client: client@example.com / client123');

        return Command::SUCCESS;
    }
}