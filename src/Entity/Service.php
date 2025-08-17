<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[Assert\NotBlank(message: 'Miasto jest wymagane.')]
    #[Assert\Length(max: 100, maxMessage: 'Nazwa miasta nie może przekraczać 100 znaków.')]
    #[ORM\Column(length: 100)]
    private string $city = '';

    #[Assert\NotBlank(message: 'Ulica jest wymagana.')]
    #[Assert\Length(max: 100, maxMessage: 'Nazwa ulicy nie może przekraczać 100 znaków.')]
    #[ORM\Column(length: 100)]
    private string $street = '';

    #[Assert\NotBlank(message: 'Kod pocztowy jest wymagany.')]
    #[Assert\Regex(
        pattern: '/^\d{2}-\d{3}$/',
        message: 'Kod pocztowy powinien mieć format XX-XXX.'
    )]
    #[ORM\Column(length: 6)]
    private string $postalCode = '';

    #[Assert\NotBlank(message: 'Numer telefonu jest wymagany.')]
    #[Assert\Regex(
        pattern: '/^\+?\d+(-\d+)*$/',
        message: 'Numer telefonu jest nieprawidłowy.'
    )]
    #[ORM\Column(length: 20)]
    private string $phoneNumber = '';

    #[ORM\ManyToOne(targetEntity: ServiceCategory::class, inversedBy: 'services')]
    private ?ServiceCategory $serviceCategory = null;

    #[ORM\Column(length: 255)]
    private string $encodedName = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Offer::class, cascade: ['persist', 'remove'])]
    private Collection $offers;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: OpeningHour::class, cascade: ['persist', 'remove'])]
    private Collection $openingHours;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Employee::class)]
    private Collection $employees;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: ServiceImage::class, cascade: ['persist', 'remove'])]
    private Collection $serviceImages;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Opinion::class, cascade: ['persist', 'remove'])]
    private Collection $opinions;

    private float $opinionsCount = 0;
    private float $averageRating = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->offers = new ArrayCollection();
        $this->openingHours = new ArrayCollection();
        $this->employees = new ArrayCollection();
        $this->serviceImages = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->opinions = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;
        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getServiceCategory(): ?ServiceCategory
    {
        return $this->serviceCategory;
    }

    public function setServiceCategory(?ServiceCategory $serviceCategory): self
    {
        $this->serviceCategory = $serviceCategory;
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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
            $offer->setService($this);
        }
        return $this;
    }

    public function removeOffer(Offer $offer): self
    {
        if ($this->offers->removeElement($offer)) {
            if ($offer->getService() === $this) {
                $offer->setService(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, OpeningHour>
     */
    public function getOpeningHours(): Collection
    {
        return $this->openingHours;
    }

    public function addOpeningHour(OpeningHour $openingHour): self
    {
        if (!$this->openingHours->contains($openingHour)) {
            $this->openingHours->add($openingHour);
            $openingHour->setService($this);
        }
        return $this;
    }

    public function removeOpeningHour(OpeningHour $openingHour): self
    {
        if ($this->openingHours->removeElement($openingHour)) {
            if ($openingHour->getService() === $this) {
                $openingHour->setService(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Employee>
     */
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function addEmployee(Employee $employee): self
    {
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
            $employee->setService($this);
        }
        return $this;
    }

    public function removeEmployee(Employee $employee): self
    {
        if ($this->employees->removeElement($employee)) {
            if ($employee->getService() === $this) {
                $employee->setService(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ServiceImage>
     */
    public function getServiceImages(): Collection
    {
        return $this->serviceImages;
    }

    public function addServiceImage(ServiceImage $serviceImage): self
    {
        if (!$this->serviceImages->contains($serviceImage)) {
            $this->serviceImages->add($serviceImage);
            $serviceImage->setService($this);
        }
        return $this;
    }

    public function removeServiceImage(ServiceImage $serviceImage): self
    {
        if ($this->serviceImages->removeElement($serviceImage)) {
            if ($serviceImage->getService() === $this) {
                $serviceImage->setService(null);
            }
        }
        return $this;
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
            $booking->setService($this);
        }
        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getService() === $this) {
                $booking->setService(null);
            }
        }
        return $this;
    }

    public function getOpinionsCount(): float
    {
        return $this->opinionsCount;
    }

    public function setOpinionsCount(float $opinionsCount): self
    {
        $this->opinionsCount = $opinionsCount;
        return $this;
    }

    public function getAverageRating(): float
    {
        return $this->averageRating;
    }

    public function setAverageRating(float $averageRating): self
    {
        $this->averageRating = $averageRating;
        return $this;
    }

    public function getOpinions(): Collection
    {
        return $this->opinions;
    }
}