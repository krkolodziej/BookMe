<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Employee;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_EMPLOYEE')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    private function getCurrentEmployee(): ?Employee
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        return $this->entityManager->getRepository(Employee::class)
            ->findOneBy(['user' => $user]);
    }

    #[Route('/', name: 'notifications_index')]
    public function index(): Response
    {
        $employee = $this->getCurrentEmployee();
        
        if (!$employee) {
            throw $this->createNotFoundException('Employee profile not found');
        }
        
        $notifications = $this->notificationRepository->findBy(
            ['employee' => $employee],
            ['createdAt' => 'DESC']
        );

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/dropdown', name: 'notifications_dropdown')]
    public function dropdown(): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $referer = $request->headers->get('referer', '');
        $isOnNotificationPage = strpos($referer, '/notifications') !== false;
        
        if ($isOnNotificationPage) {
            return new Response(
                '<div class="p-3 text-center"><span class="text-muted">Dropdown wyłączony na stronie powiadomień</span></div>'
            );
        }
        
        $employee = $this->getCurrentEmployee();
        
        if (!$employee) {
            return $this->render('notification/dropdown.html.twig', [
                'notifications' => [],
            ]);
        }
        
        $notifications = $this->notificationRepository->findBy(
            ['employee' => $employee],
            ['createdAt' => 'DESC'],
            10
        );

        return $this->render('notification/dropdown.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/mark-read/{id}', name: 'notification_mark_read', methods: ['POST'])]
    public function markAsRead(Notification $notification): JsonResponse
    {
        $employee = $this->getCurrentEmployee();
        
        if (!$employee) {
            return new JsonResponse(['success' => false, 'message' => 'Employee not found'], 404);
        }
        
        if ($notification->getEmployee() !== $employee) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->setIsRead(true);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/mark-all-read', name: 'notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $employee = $this->getCurrentEmployee();
        
        if (!$employee) {
            return new JsonResponse(['success' => false, 'message' => 'Employee not found'], 404);
        }
        
        $notifications = $this->notificationRepository->findBy([
            'employee' => $employee,
            'isRead' => false
        ]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }
        
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'count' => count($notifications)]);
    }

    #[Route('/count-unread', name: 'notifications_count_unread')]
    public function countUnread(): JsonResponse
    {
        $employee = $this->getCurrentEmployee();
        
        if (!$employee) {
            return new JsonResponse(['count' => 0]);
        }
        
        $count = $this->notificationRepository->count([
            'employee' => $employee,
            'isRead' => false
        ]);

        return new JsonResponse(['count' => $count]);
    }
}