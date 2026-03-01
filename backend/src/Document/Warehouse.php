<?php

namespace App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'warehouses')]
#[MongoDB\UniqueIndex(keys: ['code' => 'asc'])]
class Warehouse
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Warehouse name is required')]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Warehouse code is required')]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^[A-Z0-9\-]+$/', message: 'Code must be uppercase alphanumeric with hyphens')]
    private string $code = '';

    #[MongoDB\Field(type: 'string')]
    private ?string $address = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $city = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $state = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $postalCode = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $country = null;

    #[MongoDB\Field(type: 'bool')]
    private bool $isActive = true;

    /** @var Collection<int, Location> */
    #[MongoDB\EmbedMany(targetDocument: Location::class)]
    private Collection $locations;

    #[MongoDB\Field(type: 'date')]
    private \DateTimeInterface $createdAt;

    #[MongoDB\Field(type: 'date')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->locations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
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
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    /** @return Collection<int, Location> */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): self
    {
        $this->locations->add($location);
        return $this;
    }

    public function removeLocation(Location $location): self
    {
        $this->locations->removeElement($location);
        return $this;
    }

    public function findLocationById(string $locationId): ?Location
    {
        foreach ($this->locations as $location) {
            if ($location->getId() === $locationId) {
                return $location;
            }
        }
        return null;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postalCode' => $this->postalCode,
            'country' => $this->country,
            'isActive' => $this->isActive,
            'locations' => array_map(fn(Location $l) => $l->toArray(), $this->locations->toArray()),
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }
}
