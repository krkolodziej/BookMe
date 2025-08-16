<?php

namespace App\Entity;

use App\Repository\OpeningHourRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpeningHourRepository::class)]
class OpeningHour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 20)]
    private string $dayOfWeek = '';

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private \DateTimeInterface $openingTime;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private \DateTimeInterface $closingTime;

    #[ORM\Column]
    private bool $closed = false;

    #[ORM\Column]
    private int $serviceId;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'openingHours')]
    #[ORM\JoinColumn(nullable: false)]
    private Service $service;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDayOfWeek(): string
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(string $dayOfWeek): self
    {
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }

    public function getOpeningTime(): \DateTimeInterface
    {
        return $this->openingTime;
    }

    public function setOpeningTime(\DateTimeInterface $openingTime): self
    {
        $this->openingTime = $openingTime;
        return $this;
    }

    public function getClosingTime(): \DateTimeInterface
    {
        return $this->closingTime;
    }

    public function setClosingTime(\DateTimeInterface $closingTime): self
    {
        $this->closingTime = $closingTime;
        return $this;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;
        return $this;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function setServiceId(int $serviceId): self
    {
        $this->serviceId = $serviceId;
        return $this;
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function setService(Service $service): self
    {
        $this->service = $service;
        $this->serviceId = $service->getId();
        return $this;
    }
}