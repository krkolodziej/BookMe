<?php

namespace App\Entity;

use App\Repository\ServiceImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceImageRepository::class)]
class ServiceImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $url = '';

    #[ORM\Column]
    private int $serviceId;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'serviceImages')]
    #[ORM\JoinColumn(nullable: false)]
    private Service $service;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
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