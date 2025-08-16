<?php

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column]
    private int $duration;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column]
    private int $serviceId;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'offers')]
    #[ORM\JoinColumn(nullable: false)]
    private Service $service;

    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: Booking::class)]
    private Collection $bookings;

    #[ORM\Column(length: 255)]
    private string $encodedName = '';

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->encodeName();
        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
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

    public function getEncodedName(): string
    {
        return $this->encodedName;
    }

    private function encodeName(): void
    {
        $this->encodedName = strtolower(
            trim(
                preg_replace(
                    '/[^a-zA-Z0-9]+/',
                    '-',
                    iconv('UTF-8', 'ASCII//TRANSLIT', $this->name)
                )
            )
        );
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setOffer($this);
        }
        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getOffer() === $this) {
                $booking->setOffer(null);
            }
        }
        return $this;
    }
}