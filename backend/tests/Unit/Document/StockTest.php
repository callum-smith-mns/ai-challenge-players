<?php

namespace App\Tests\Unit\Document;

use App\Document\Stock;
use PHPUnit\Framework\TestCase;

class StockTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $stock = new Stock();

        $this->assertNull($stock->getId());
        $this->assertSame('', $stock->getProductId());
        $this->assertSame('', $stock->getWarehouseId());
        $this->assertSame('', $stock->getLocationId());
        $this->assertSame(0, $stock->getQuantity());
        $this->assertNull($stock->getBatchNumber());
        $this->assertNull($stock->getExpiryDate());
    }

    public function testSettersAndGetters(): void
    {
        $stock = new Stock();

        $stock->setProductId('prod-1');
        $this->assertSame('prod-1', $stock->getProductId());

        $stock->setWarehouseId('wh-1');
        $this->assertSame('wh-1', $stock->getWarehouseId());

        $stock->setLocationId('loc-1');
        $this->assertSame('loc-1', $stock->getLocationId());

        $stock->setQuantity(100);
        $this->assertSame(100, $stock->getQuantity());

        $stock->setBatchNumber('BATCH-001');
        $this->assertSame('BATCH-001', $stock->getBatchNumber());

        $expiry = new \DateTimeImmutable('2025-12-31');
        $stock->setExpiryDate($expiry);
        $this->assertSame($expiry, $stock->getExpiryDate());
    }

    public function testAddQuantity(): void
    {
        $stock = new Stock();
        $stock->setQuantity(10);

        $stock->addQuantity(5);
        $this->assertSame(15, $stock->getQuantity());

        $stock->addQuantity(25);
        $this->assertSame(40, $stock->getQuantity());
    }

    public function testRemoveQuantity(): void
    {
        $stock = new Stock();
        $stock->setQuantity(20);

        $stock->removeQuantity(5);
        $this->assertSame(15, $stock->getQuantity());

        $stock->removeQuantity(15);
        $this->assertSame(0, $stock->getQuantity());
    }

    public function testRemoveQuantityThrowsWhenInsufficient(): void
    {
        $stock = new Stock();
        $stock->setQuantity(5);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot remove more stock than available');

        $stock->removeQuantity(10);
    }

    public function testToArray(): void
    {
        $stock = new Stock();
        $stock->setProductId('prod-1');
        $stock->setWarehouseId('wh-1');
        $stock->setLocationId('loc-1');
        $stock->setQuantity(50);
        $stock->setBatchNumber('B001');

        $array = $stock->toArray();

        $this->assertSame('prod-1', $array['productId']);
        $this->assertSame('wh-1', $array['warehouseId']);
        $this->assertSame('loc-1', $array['locationId']);
        $this->assertSame(50, $array['quantity']);
        $this->assertSame('B001', $array['batchNumber']);
    }
}
