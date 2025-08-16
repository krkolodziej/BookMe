<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private Security $security
    ) {}

    public function getGlobals(): array
    {
        $user = $this->security->getUser();
        $unreadCount = 0;

        if ($user && $this->security->isGranted('ROLE_EMPLOYEE')) {
           
            $employee = $this->notificationRepository->getEntityManager()
                ->getRepository(\App\Entity\Employee::class)
                ->findOneBy(['user' => $user]);
            
            if ($employee) {
                $unreadCount = $this->notificationRepository->count([
                    'employee' => $employee,
                    'isRead' => false
                ]);
            }
        }

        return [
            'unread_notifications_count' => $unreadCount,
        ];
    }
}