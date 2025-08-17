<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Opinion;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\OpinionRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class OpinionService
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository,
        private readonly OpinionRepository $opinionRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    /** 
     * @param string $encodedName
     * @param array $params
     * @return array
     * @throws NotFoundHttpException
     */
    public function getServiceOpinions(string $encodedName, array $params): array
    {
        $page = max(1, $params['page'] ?? 1);
        $pageSize = max(1, $params['pageSize'] ?? 10);
        $sorts = $params['sorts'] ?? '-createdAt';

        $service = $this->serviceRepository->findOneBy(['encodedName' => $encodedName]);

        if (!$service) {
            throw new NotFoundHttpException('Serwis nie został znaleziony');
        }

        $result = $this->opinionRepository->getServiceOpinions(
            $service,
            $page,
            $pageSize,
            $sorts
        );

        return [
            'items' => $result['items'],
            'totalPages' => $result['totalPages'],
            'currentPage' => $page,
            'pageSize' => $pageSize,
            'total' => $result['total']
        ];
    }

    /** 
     * @param int $bookingId
     * @return Booking
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function getBookingForOpinion(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->find($bookingId);

        if (!$booking) {
            throw new NotFoundHttpException('Rezerwacja nie została znaleziona.');
        }

        $now = new \DateTime();
        if ($booking->getEndTime() > $now) {
            throw new BadRequestHttpException('Nie można dodać opinii dla niezakończonej wizyty.');
        }

        if ($booking->getOpinion() !== null) {
            throw new BadRequestHttpException('Opinia dla tej wizyty już istnieje.');
        }

        return $booking;
    }

    /** 
     * @param Booking $booking
     * @param User|null $user
     * @return Opinion
     */
    public function createOpinion(Booking $booking, ?User $user): Opinion
    {
        $opinion = new Opinion();
        $opinion->setBooking($booking);
        $opinion->setService($booking->getService());
        $opinion->setCreatedAt(new \DateTimeImmutable());
        
        if (method_exists($opinion, 'setUser') && $user) {
            $opinion->setUser($user);
        }

        return $opinion;
    }

    /** 
     * @param Opinion $opinion
     * @return void
     */
    public function saveOpinion(Opinion $opinion): void
    {
        $this->entityManager->persist($opinion);
        $this->entityManager->flush();
    }

    /** 
     * @param int $opinionId
     * @param User $user
     * @return Opinion
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function getOpinionForEdit(int $opinionId, User $user): Opinion
    {
        $opinion = $this->opinionRepository->find($opinionId);

        if (!$opinion) {
            throw new NotFoundHttpException('Opinia nie została znaleziona.');
        }

        $booking = $opinion->getBooking();
        if ($booking->getUser() !== $user) {
            throw new AccessDeniedException('Nie masz uprawnień do edycji tej opinii.');
        }

        return $opinion;
    }

    /** 
     * @param int $opinionId
     * @param User $user
     * @param string $csrfToken
     * @return void
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     * @throws BadRequestHttpException
     */
    public function deleteOpinion(int $opinionId, User $user, string $csrfToken): void
    {
        $opinion = $this->opinionRepository->find($opinionId);

        if (!$opinion) {
            throw new NotFoundHttpException('Opinia nie została znaleziona.');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete-opinion-' . $opinionId, $csrfToken))) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        $booking = $opinion->getBooking();
        if ($booking->getUser() !== $user) {
            throw new AccessDeniedException('Nie masz uprawnień do usunięcia tej opinii.');
        }


        $this->opinionRepository->remove($opinion, true);
    }
} 