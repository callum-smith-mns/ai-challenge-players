<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'stock')]
#[MongoDB\Index(keys: ['productId' => 'asc', 'warehouseId' => 'asc', 'locationId' => 'asc'])]
#[MongoDB\Index(keys: ['productId' => 'asc'])]
#[MongoDB\Index(keys: ['warehouseId' => 'asc'])]
class Stock
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $productId = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $warehouseId = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $locationId = '';

    #[MongoDB\Field(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $quantity = 0;

    #[MongoDB\Field(type: 'string')]
    private ?string $batchNumber = null;

    #[MongoDB\Field(type: 'date')]
    private ?\DateTimeInterface $expiryDate = null;

    #[MongoDB\Field(type: 'date')]
    private \DateTimeInterface $createdAt;

    #[MongoDB\Field(type: 'date')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function getWarehouseId(): string
    {
        return $this->warehouseId;
    }

    public function setWarehouseId(string $warehouseId): self
    {
        $this->warehouseId = $warehouseId;
        return $this;
    }

    public function getLocationId(): string
    {
        return $this->locationId;
    }

    public function setLocationId(string $locationId): self
    {
        $this->locationId = $locationId;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function addQuantity(int $quantity): self
    {
        $this->quantity += $quantity;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function removeQuantity(int $quantity): self
    {
        if ($quantity > $this->quantity) {
            throw new \InvalidArgumentException('Cannot remove more stock than available');
        }
        $this->quantity -= $quantity;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getBatchNumber(): ?string
    {
        return $this->batchNumber;
    }

    public function setBatchNumber(?string $batchNumber): self
    {
        $this->batchNumber = $batchNumber;
        return $this;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeInterface $expiryDate): self
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->productId,
            'warehouseId' => $this->warehouseId,
            'locationId' => $this->locationId,
            'quantity' => $this->quantity,
            'batchNumber' => $this->batchNumber,
            'expiryDate' => $this->expiryDate?->format('c'),
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }
}
