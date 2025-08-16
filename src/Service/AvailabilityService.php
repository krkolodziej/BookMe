<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\Offer;
use App\Entity\Service;
use App\Repository\BookingRepository;
use App\Repository\OpeningHourRepository;
use Symfony\Bundle\SecurityBundle\Security;

class AvailabilityService
{
    private const DAYS_OF_WEEK_MAP = [
        'Monday' => 'poniedziałek',
        'Tuesday' => 'wtorek',
        'Wednesday' => 'środa',
        'Thursday' => 'thursday',
        'Friday' => 'piątek',
        'Saturday' => 'sobota',
        'Sunday' => 'niedziela'
    ];

    public function __construct(
        private readonly OpeningHourRepository $openingHourRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly Security $security
    ) {
    }

    public function getAvailableSlots(
        Service $service,
        Offer $offer,
        Employee $employee,
        \DateTime $date
    ): array {
        $dayOfWeek = self::DAYS_OF_WEEK_MAP[$date->format('l')];
        $openingHour = $this->openingHourRepository->findOneBy([
            'service' => $service,
            'dayOfWeek' => $dayOfWeek
        ]);

        if (!$openingHour || $openingHour->isClosed()) {
            return [];
        }

        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        $startOfDay = clone $date;
        $startOfDay->setTime(
            (int)$openingHour->getOpeningTime()->format('H'),
            (int)$openingHour->getOpeningTime()->format('i')
        );

        $endOfDay = clone $date;
        $endOfDay->setTime(
            (int)$openingHour->getClosingTime()->format('H'),
            (int)$openingHour->getClosingTime()->format('i')
        );

        $employeeBookings = $this->bookingRepository->findConflictingBookings(
            $employee,
            $startOfDay,
            $endOfDay
        );

        $userBookings = $this->bookingRepository->findUserBookings(
            $user,
            $startOfDay,
            $endOfDay
        );

        $availableSlots = [];
        $currentSlot = clone $startOfDay;
        $offerDuration = new \DateInterval('PT' . $offer->getDuration() . 'M');

        while ($currentSlot < $endOfDay) {
            $slotEnd = (clone $currentSlot)->add($offerDuration);

            $isAvailable = true;

            foreach ($employeeBookings as $booking) {
                if ($this->isTimeOverlapping(
                    $currentSlot,
                    $slotEnd,
                    $booking->getStartTime(),
                    $booking->getEndTime()
                )) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                foreach ($userBookings as $booking) {
                    if ($this->isTimeOverlapping(
                        $currentSlot,
                        $slotEnd,
                        $booking->getStartTime(),
                        $booking->getEndTime()
                    )) {
                        $isAvailable = false;
                        break;
                    }
                }
            }

            if ($isAvailable && $slotEnd <= $endOfDay) {
                $availableSlots[] = clone $currentSlot;
            }

            $currentSlot->add(new \DateInterval('PT' . $offer->getDuration() . 'M'));
        }

        return $availableSlots;
    }

    private function isTimeOverlapping(
        \DateTime $start1,
        \DateTime $end1,
        \DateTime $start2,
        \DateTime $end2
    ): bool {
        return $start1 < $end2 && $start2 < $end1;
    }
}