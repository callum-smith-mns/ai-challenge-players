<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\EmbeddedDocument]
class Location
{
    #[MongoDB\Id(strategy: 'NONE', type: 'string')]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Location name is required')]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Location type is required')]
    #[Assert\Choice(
        choices: ['storage', 'picking', 'picked', 'receiving'],
        message: 'Location type must be one of: storage, picking, picked, receiving'
    )]
    private string $type = '';

    #[MongoDB\Field(type: 'string')]
    private ?string $aisle = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $rack = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $shelf = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $bin = null;

    #[MongoDB\Field(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $capacity = 0;

    #[MongoDB\Field(type: 'bool')]
    private bool $isActive = true;

    #[MongoDB\Field(type: 'date')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getAisle(): ?string
    {
        return $this->aisle;
    }

    public function setAisle(?string $aisle): self
    {
        $this->aisle = $aisle;
        return $this;
    }

    public function getRack(): ?string
    {
        return $this->rack;
    }

    public function setRack(?string $rack): self
    {
        $this->rack = $rack;
        return $this;
    }

    public function getShelf(): ?string
    {
        return $this->shelf;
    }

    public function setShelf(?string $shelf): self
    {
        $this->shelf = $shelf;
        return $this;
    }

    public function getBin(): ?string
    {
        return $this->bin;
    }

    public function setBin(?string $bin): self
    {
        $this->bin = $bin;
        return $this;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'aisle' => $this->aisle,
            'rack' => $this->rack,
            'shelf' => $this->shelf,
            'bin' => $this->bin,
            'capacity' => $this->capacity,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}
