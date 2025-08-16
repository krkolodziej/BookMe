<?php

namespace App\DTO;

class AvailabilitySlot
{
    public function __construct(
        private \DateTime $startTime,
        private \DateTime $endTime,
        private bool $isAvailable
    ) {
    }

    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }

    public function getEndTime(): \DateTime
    {
        return $this->endTime;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function toArray(): array
    {
        return [
            'start' => $this->startTime->format('Y-m-d\TH:i:s'),
            'end' => $this->endTime->format('Y-m-d\TH:i:s'),
            'isAvailable' => $this->isAvailable
        ];
    }
}