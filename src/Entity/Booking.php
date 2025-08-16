<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private int $userId;

    #[ORM\Column]
    private int $offerId;

    #[ORM\Column]
    private int $serviceId;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'Data rozpoczęcia jest wymagana.')]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $endTime;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bookings', cascade: ['persist'])]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Employee::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: "employee_id", referencedColumnName: "id", nullable: true)]
    private ?Employee $employee = null;

    #[ORM\ManyToOne(targetEntity: Offer::class, inversedBy: 'bookings', cascade: ['persist'])]
    #[ORM\JoinColumn(name: "offer_id", referencedColumnName: "id", nullable: false)]
    private Offer $offer;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'bookings', cascade: ['persist'])]
    #[ORM\JoinColumn(name: "service_id", referencedColumnName: "id", nullable: false)]
    private Service $service;

    #[ORM\OneToOne(mappedBy: 'booking', targetEntity: Opinion::class, cascade: ['persist'])]
    private ?Opinion $opinion = null;



    public function __construct()
    {
        $this->employee = null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }


    public function getOfferId(): int
    {
        return $this->offerId;
    }

    public function setOfferId(int $offerId): self
    {
        $this->offerId = $offerId;
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

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(): self
    {
        if ($this->offer !== null && $this->startTime !== null) {
            $this->endTime = (clone $this->startTime)->modify('+' . $this->offer->getDuration() . ' minutes');
        }
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        $this->userId = $user->getId();
        return $this;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;
        $this->employeeId = $employee !== null ? $employee->getId() : null;
        return $this;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer): self
    {
        $this->offer = $offer;
        $this->offerId = $offer->getId();
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

    public function getOpinion(): ?Opinion
    {
        return $this->opinion;
    }

    public function setOpinion(?Opinion $opinion): self
    {
        $this->opinion = $opinion;
        return $this;
    }
}