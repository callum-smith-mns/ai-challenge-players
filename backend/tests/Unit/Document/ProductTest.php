<?php

namespace App\Tests\Unit\Document;

use App\Document\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $product = new Product();

        $this->assertNull($product->getId());
        $this->assertSame('', $product->getUpc());
        $this->assertNull($product->getEan());
        $this->assertSame('', $product->getName());
        $this->assertNull($product->getDescription());
        $this->assertSame('', $product->getBrand());
        $this->assertSame('', $product->getCategory());
        $this->assertSame(0.0, $product->getWeight());
        $this->assertSame('g', $product->getWeightUnit());
        $this->assertNull($product->getImageUrl());
        $this->assertSame([], $product->getIngredients());
        $this->assertSame([], $product->getAllergens());
        $this->assertSame([], $product->getNutritionalInfo());
        $this->assertNull($product->getStorageInstructions());
        $this->assertSame(0, $product->getShelfLifeDays());
        $this->assertNull($product->getCountryOfOrigin());
        $this->assertTrue($product->getIsActive());
        $this->assertInstanceOf(\DateTimeInterface::class, $product->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $product->getUpdatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $product = new Product();

        $product->setUpc('012345678901');
        $this->assertSame('012345678901', $product->getUpc());

        $product->setEan('0123456789012');
        $this->assertSame('0123456789012', $product->getEan());

        $product->setName('Test Product');
        $this->assertSame('Test Product', $product->getName());

        $product->setDescription('A test description');
        $this->assertSame('A test description', $product->getDescription());

        $product->setBrand('Test Brand');
        $this->assertSame('Test Brand', $product->getBrand());

        $product->setCategory('Dairy');
        $this->assertSame('Dairy', $product->getCategory());

        $product->setWeight(500.5);
        $this->assertSame(500.5, $product->getWeight());

        $product->setWeightUnit('kg');
        $this->assertSame('kg', $product->getWeightUnit());

        $product->setImageUrl('https://example.com/image.jpg');
        $this->assertSame('https://example.com/image.jpg', $product->getImageUrl());

        $product->setIngredients(['milk', 'sugar']);
        $this->assertSame(['milk', 'sugar'], $product->getIngredients());

        $product->setAllergens(['lactose']);
        $this->assertSame(['lactose'], $product->getAllergens());

        $product->setNutritionalInfo(['calories' => 120]);
        $this->assertSame(['calories' => 120], $product->getNutritionalInfo());

        $product->setStorageInstructions('Keep refrigerated');
        $this->assertSame('Keep refrigerated', $product->getStorageInstructions());

        $product->setShelfLifeDays(7);
        $this->assertSame(7, $product->getShelfLifeDays());

        $product->setCountryOfOrigin('US');
        $this->assertSame('US', $product->getCountryOfOrigin());

        $product->setIsActive(false);
        $this->assertFalse($product->getIsActive());
    }

    public function testSettersFluent(): void
    {
        $product = new Product();

        $result = $product->setUpc('012345678901')
            ->setName('Test')
            ->setBrand('Brand')
            ->setCategory('Cat')
            ->setWeight(100);

        $this->assertSame($product, $result);
    }

    public function testToArray(): void
    {
        $product = new Product();
        $product->setUpc('012345678901');
        $product->setName('Test Product');
        $product->setBrand('TestBrand');
        $product->setCategory('Dairy');
        $product->setWeight(500);
        $product->setWeightUnit('g');
        $product->setIngredients(['milk']);
        $product->setAllergens(['lactose']);

        $array = $product->toArray();

        $this->assertSame('012345678901', $array['upc']);
        $this->assertSame('Test Product', $array['name']);
        $this->assertSame('TestBrand', $array['brand']);
        $this->assertSame('Dairy', $array['category']);
        $this->assertSame(500.0, $array['weight']);
        $this->assertSame('g', $array['weightUnit']);
        $this->assertSame(['milk'], $array['ingredients']);
        $this->assertSame(['lactose'], $array['allergens']);
        $this->assertTrue($array['isActive']);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);
    }
}
