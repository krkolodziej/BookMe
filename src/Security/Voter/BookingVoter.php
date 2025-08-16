<?php

namespace App\Security\Voter;

use App\Entity\Booking;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class BookingVoter extends Voter
{
    const VIEW = 'VIEW';
    const EDIT = 'EDIT';
    const CANCEL = 'CANCEL';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Booking) {
            return false;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::CANCEL]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Booking $booking */
        $booking = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($booking, $user);
            case self::EDIT:
                return $this->canEdit($booking, $user);
            case self::CANCEL:
                return $this->canCancel($booking, $user);
        }

        return false;
    }

    private function canView(Booking $booking, UserInterface $user): bool
    {
        if ($booking->getUser() && $booking->getUser()->getId() === $user->getId()) {
            return true;
        }

        if ($this->security->isGranted('ROLE_EMPLOYEE') && $booking->getEmployee() && $booking->getEmployee()->getUser() === $user) {
            return true;
        }

        return false;
    }

    private function canEdit(Booking $booking, UserInterface $user): bool
    {
        $now = new \DateTime();
        if ($booking->getStartTime() < $now) {
            return false;
        }

        if ($booking->getUser() && $booking->getUser()->getId() === $user->getId()) {
            $editCutoff = clone $booking->getStartTime();
            $editCutoff->modify('-24 hours');

            return $now < $editCutoff;
        }

        return false;
    }

    private function canCancel(Booking $booking, UserInterface $user): bool
    {
        $now = new \DateTime();
        if ($booking->getStartTime() < $now) {
            return false;
        }

        if ($booking->getUser() && $booking->getUser()->getId() === $user->getId()) {
            $cancelCutoff = clone $booking->getStartTime();
            $cancelCutoff->modify('-24 hours');

            return $now < $cancelCutoff;
        }

        if ($this->security->isGranted('ROLE_EMPLOYEE') && $booking->getEmployee() && $booking->getEmployee()->getUser() === $user) {
            return true;
        }

        return false;
    }
}