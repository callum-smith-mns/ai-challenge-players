<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'products')]
#[MongoDB\UniqueIndex(keys: ['upc' => 'asc'])]
#[MongoDB\Index(keys: ['ean' => 'asc'])]
class Product
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'UPC is required')]
    #[Assert\Regex(pattern: '/^\d{12}$/', message: 'UPC must be exactly 12 digits')]
    private string $upc = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\Regex(pattern: '/^\d{13}$/', message: 'EAN must be exactly 13 digits')]
    private ?string $ean = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Product name is required')]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Brand is required')]
    private string $brand = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Category is required')]
    private string $category = '';

    #[MongoDB\Field(type: 'float')]
    #[Assert\NotBlank(message: 'Weight is required')]
    #[Assert\Positive(message: 'Weight must be positive')]
    private float $weight = 0.0;

    #[MongoDB\Field(type: 'string')]
    #[Assert\Choice(choices: ['g', 'kg', 'oz', 'lb'], message: 'Invalid weight unit')]
    private string $weightUnit = 'g';

    #[MongoDB\Field(type: 'string')]
    private ?string $imageUrl = null;

    #[MongoDB\Field(type: 'collection')]
    private array $ingredients = [];

    #[MongoDB\Field(type: 'collection')]
    private array $allergens = [];

    #[MongoDB\Field(type: 'hash')]
    private array $nutritionalInfo = [];

    #[MongoDB\Field(type: 'string')]
    private ?string $storageInstructions = null;

    #[MongoDB\Field(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $shelfLifeDays = 0;

    #[MongoDB\Field(type: 'string')]
    private ?string $countryOfOrigin = null;

    #[MongoDB\Field(type: 'bool')]
    private bool $isActive = true;

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

    public function getUpc(): string
    {
        return $this->upc;
    }

    public function setUpc(string $upc): self
    {
        $this->upc = $upc;
        return $this;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): self
    {
        $this->ean = $ean;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function getWeightUnit(): string
    {
        return $this->weightUnit;
    }

    public function setWeightUnit(string $weightUnit): self
    {
        $this->weightUnit = $weightUnit;
        return $this;
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

    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function setIngredients(array $ingredients): self
    {
        $this->ingredients = $ingredients;
        return $this;
    }

    public function getAllergens(): array
    {
        return $this->allergens;
    }

    public function setAllergens(array $allergens): self
    {
        $this->allergens = $allergens;
        return $this;
    }

    public function getNutritionalInfo(): array
    {
        return $this->nutritionalInfo;
    }

    public function setNutritionalInfo(array $nutritionalInfo): self
    {
        $this->nutritionalInfo = $nutritionalInfo;
        return $this;
    }

    public function getStorageInstructions(): ?string
    {
        return $this->storageInstructions;
    }

    public function setStorageInstructions(?string $storageInstructions): self
    {
        $this->storageInstructions = $storageInstructions;
        return $this;
    }

    public function getShelfLifeDays(): int
    {
        return $this->shelfLifeDays;
    }

    public function setShelfLifeDays(int $shelfLifeDays): self
    {
        $this->shelfLifeDays = $shelfLifeDays;
        return $this;
    }

    public function getCountryOfOrigin(): ?string
    {
        return $this->countryOfOrigin;
    }

    public function setCountryOfOrigin(?string $countryOfOrigin): self
    {
        $this->countryOfOrigin = $countryOfOrigin;
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
            'upc' => $this->upc,
            'ean' => $this->ean,
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'category' => $this->category,
            'weight' => $this->weight,
            'weightUnit' => $this->weightUnit,
            'imageUrl' => $this->imageUrl,
            'ingredients' => $this->ingredients,
            'allergens' => $this->allergens,
            'nutritionalInfo' => $this->nutritionalInfo,
            'storageInstructions' => $this->storageInstructions,
            'shelfLifeDays' => $this->shelfLifeDays,
            'countryOfOrigin' => $this->countryOfOrigin,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }
}
