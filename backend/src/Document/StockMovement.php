<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'stock_movements')]
#[MongoDB\Index(keys: ['productId' => 'asc'])]
#[MongoDB\Index(keys: ['warehouseId' => 'asc'])]
#[MongoDB\Index(keys: ['movementType' => 'asc'])]
#[MongoDB\Index(keys: ['createdAt' => 'desc'])]
class StockMovement
{
    public const TYPE_RECEIVE = 'receive';
    public const TYPE_STORE = 'store';
    public const TYPE_PICK = 'pick';
    public const TYPE_PACK = 'pack';
    public const TYPE_SHIP = 'ship';
    public const TYPE_TRANSFER = 'transfer';

    public const ALL_TYPES = [
        self::TYPE_RECEIVE,
        self::TYPE_STORE,
        self::TYPE_PICK,
        self::TYPE_PACK,
        self::TYPE_SHIP,
        self::TYPE_TRANSFER,
    ];

    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $productId = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $warehouseId = '';

    #[MongoDB\Field(type: 'string')]
    private ?string $fromLocationId = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $toLocationId = null;

    #[MongoDB\Field(type: 'int')]
    #[Assert\Positive(message: 'Quantity must be positive')]
    private int $quantity = 0;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: self::ALL_TYPES, message: 'Invalid movement type')]
    private string $movementType = '';

    #[MongoDB\Field(type: 'string')]
    private ?string $reference = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $notes = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $batchNumber = null;

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

    public function getFromLocationId(): ?string
    {
        return $this->fromLocationId;
    }

    public function setFromLocationId(?string $fromLocationId): self
    {
        $this->fromLocationId = $fromLocationId;
        return $this;
    }

    public function getToLocationId(): ?string
    {
        return $this->toLocationId;
    }

    public function setToLocationId(?string $toLocationId): self
    {
        $this->toLocationId = $toLocationId;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getMovementType(): string
    {
        return $this->movementType;
    }

    public function setMovementType(string $movementType): self
    {
        $this->movementType = $movementType;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->productId,
            'warehouseId' => $this->warehouseId,
            'fromLocationId' => $this->fromLocationId,
            'toLocationId' => $this->toLocationId,
            'quantity' => $this->quantity,
            'movementType' => $this->movementType,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'batchNumber' => $this->batchNumber,
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}
