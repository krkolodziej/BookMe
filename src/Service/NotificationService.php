<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\User;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

 
    private function findEmployeeByUser(User $user): ?Employee
    {
        return $this->entityManager->getRepository(Employee::class)
            ->findOneBy(['user' => $user]);
    }

    
    public function createNotification(Employee $employee, string $message): Notification
    {
        $notification = new Notification();
        $notification->setEmployee($employee);
        $notification->setMessage($message);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

   
    public function createNotificationForUser(User $user, string $message): ?Notification
    {
        $employee = $this->findEmployeeByUser($user);
        
        if (!$employee) {
            return null;
        }

        return $this->createNotification($employee, $message);
    }

   
    public function createNotificationsForEmployees(array $employees, string $message): array
    {
        $notifications = [];

        foreach ($employees as $employee) {
            $notification = new Notification();
            $notification->setEmployee($employee);
            $notification->setMessage($message);
            
            $this->entityManager->persist($notification);
            $notifications[] = $notification;
        }

        $this->entityManager->flush();

        return $notifications;
    }

   
    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $this->entityManager->flush();
    }


    public function markAllAsRead(Employee $employee): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $result = $qb->update(Notification::class, 'n')
            ->set('n.isRead', ':isRead')
            ->where('n.employee = :employee')
            ->andWhere('n.isRead = :currentIsRead')
            ->setParameter('isRead', true)
            ->setParameter('employee', $employee)
            ->setParameter('currentIsRead', false)
            ->getQuery()
            ->execute();

        return $result;
    }


    public function markAllAsReadForUser(User $user): int
    {
        $employee = $this->findEmployeeByUser($user);
        
        if (!$employee) {
            return 0;
        }

        return $this->markAllAsRead($employee);
    }


    public function getUnreadCount(Employee $employee): int
    {
        return $this->entityManager->getRepository(Notification::class)->count([
            'employee' => $employee,
            'isRead' => false
        ]);
    }


    public function getUnreadCountForUser(User $user): int
    {
        $employee = $this->findEmployeeByUser($user);
        
        if (!$employee) {
            return 0;
        }

        return $this->getUnreadCount($employee);
    }


    public function cleanOldNotifications(int $daysOld = 30): int
    {
        $dateThreshold = new \DateTimeImmutable("-{$daysOld} days");
        
        $qb = $this->entityManager->createQueryBuilder();
        
        $result = $qb->delete(Notification::class, 'n')
            ->where('n.createdAt < :threshold')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('threshold', $dateThreshold)
            ->setParameter('isRead', true)
            ->getQuery()
            ->execute();

        return $result;
    }


    public function notifyNewBooking(Employee $employee, string $clientName, string $serviceName, \DateTimeInterface $bookingDate): Notification
    {
        $message = sprintf(
            'Nowa rezerwacja od %s na usługę "%s" w dniu %s.',
            $clientName,
            $serviceName,
            $bookingDate->format('d.m.Y H:i')
        );

        return $this->createNotification($employee, $message);
    }


    public function notifyBookingCancellation(Employee $employee, string $clientName, string $serviceName, \DateTimeInterface $bookingDate): Notification
    {
        $message = sprintf(
            'Anulowano rezerwację od %s na usługę "%s" zaplanowaną na %s.',
            $clientName,
            $serviceName,
            $bookingDate->format('d.m.Y H:i')
        );

        return $this->createNotification($employee, $message);
    }


    public function notifyUpcomingBooking(Employee $employee, string $clientName, string $serviceName, \DateTimeInterface $bookingDate): Notification
    {
        $message = sprintf(
            'Przypomnienie: Za godzinę masz rezerwację z %s na usługę "%s".',
            $clientName,
            $serviceName
        );

        return $this->createNotification($employee, $message);
    }
}