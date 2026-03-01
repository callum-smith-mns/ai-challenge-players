<?php

namespace App\Tests\Unit\Document;

use App\Document\StockMovement;
use PHPUnit\Framework\TestCase;

class StockMovementTest extends TestCase
{
    public function testConstants(): void
    {
        $this->assertSame('receive', StockMovement::TYPE_RECEIVE);
        $this->assertSame('store', StockMovement::TYPE_STORE);
        $this->assertSame('pick', StockMovement::TYPE_PICK);
        $this->assertSame('pack', StockMovement::TYPE_PACK);
        $this->assertSame('ship', StockMovement::TYPE_SHIP);
        $this->assertSame('transfer', StockMovement::TYPE_TRANSFER);
        $this->assertCount(6, StockMovement::ALL_TYPES);
    }

    public function testConstructorSetsDefaults(): void
    {
        $movement = new StockMovement();

        $this->assertNull($movement->getId());
        $this->assertSame('', $movement->getProductId());
        $this->assertSame('', $movement->getWarehouseId());
        $this->assertNull($movement->getFromLocationId());
        $this->assertNull($movement->getToLocationId());
        $this->assertSame(0, $movement->getQuantity());
        $this->assertSame('', $movement->getMovementType());
        $this->assertNull($movement->getReference());
        $this->assertNull($movement->getNotes());
        $this->assertNull($movement->getBatchNumber());
        $this->assertInstanceOf(\DateTimeInterface::class, $movement->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $movement = new StockMovement();

        $movement->setProductId('prod-1');
        $this->assertSame('prod-1', $movement->getProductId());

        $movement->setWarehouseId('wh-1');
        $this->assertSame('wh-1', $movement->getWarehouseId());

        $movement->setFromLocationId('loc-from');
        $this->assertSame('loc-from', $movement->getFromLocationId());

        $movement->setToLocationId('loc-to');
        $this->assertSame('loc-to', $movement->getToLocationId());

        $movement->setQuantity(25);
        $this->assertSame(25, $movement->getQuantity());

        $movement->setMovementType('receive');
        $this->assertSame('receive', $movement->getMovementType());

        $movement->setReference('REF-001');
        $this->assertSame('REF-001', $movement->getReference());

        $movement->setNotes('Test note');
        $this->assertSame('Test note', $movement->getNotes());

        $movement->setBatchNumber('BATCH-001');
        $this->assertSame('BATCH-001', $movement->getBatchNumber());
    }

    public function testToArray(): void
    {
        $movement = new StockMovement();
        $movement->setProductId('prod-1');
        $movement->setWarehouseId('wh-1');
        $movement->setFromLocationId('loc-1');
        $movement->setToLocationId('loc-2');
        $movement->setQuantity(10);
        $movement->setMovementType('transfer');
        $movement->setReference('REF-001');

        $array = $movement->toArray();

        $this->assertSame('prod-1', $array['productId']);
        $this->assertSame('wh-1', $array['warehouseId']);
        $this->assertSame('loc-1', $array['fromLocationId']);
        $this->assertSame('loc-2', $array['toLocationId']);
        $this->assertSame(10, $array['quantity']);
        $this->assertSame('transfer', $array['movementType']);
        $this->assertSame('REF-001', $array['reference']);
    }
}
