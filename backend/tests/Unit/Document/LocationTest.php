<?php

namespace App\Tests\Unit\Document;

use App\Document\Location;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $location = new Location();

        $this->assertNull($location->getId());
        $this->assertSame('', $location->getName());
        $this->assertSame('', $location->getType());
        $this->assertNull($location->getAisle());
        $this->assertNull($location->getRack());
        $this->assertNull($location->getShelf());
        $this->assertNull($location->getBin());
        $this->assertSame(0, $location->getCapacity());
        $this->assertTrue($location->getIsActive());
        $this->assertInstanceOf(\DateTimeInterface::class, $location->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $location = new Location();

        $location->setId('test-id');
        $this->assertSame('test-id', $location->getId());

        $location->setName('A-01-01');
        $this->assertSame('A-01-01', $location->getName());

        $location->setType('storage');
        $this->assertSame('storage', $location->getType());

        $location->setAisle('A');
        $this->assertSame('A', $location->getAisle());

        $location->setRack('01');
        $this->assertSame('01', $location->getRack());

        $location->setShelf('03');
        $this->assertSame('03', $location->getShelf());

        $location->setBin('B1');
        $this->assertSame('B1', $location->getBin());

        $location->setCapacity(100);
        $this->assertSame(100, $location->getCapacity());

        $location->setIsActive(false);
        $this->assertFalse($location->getIsActive());
    }

    public function testToArray(): void
    {
        $location = new Location();
        $location->setId('loc-1');
        $location->setName('RCV-01');
        $location->setType('receiving');
        $location->setAisle('R');
        $location->setCapacity(500);

        $array = $location->toArray();

        $this->assertSame('loc-1', $array['id']);
        $this->assertSame('RCV-01', $array['name']);
        $this->assertSame('receiving', $array['type']);
        $this->assertSame('R', $array['aisle']);
        $this->assertSame(500, $array['capacity']);
        $this->assertTrue($array['isActive']);
    }
}
