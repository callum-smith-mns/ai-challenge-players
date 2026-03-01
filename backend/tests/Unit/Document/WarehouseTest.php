<?php

namespace App\Tests\Unit\Document;

use App\Document\Location;
use App\Document\Warehouse;
use PHPUnit\Framework\TestCase;

class WarehouseTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $warehouse = new Warehouse();

        $this->assertNull($warehouse->getId());
        $this->assertSame('', $warehouse->getName());
        $this->assertSame('', $warehouse->getCode());
        $this->assertNull($warehouse->getAddress());
        $this->assertNull($warehouse->getCity());
        $this->assertNull($warehouse->getState());
        $this->assertNull($warehouse->getPostalCode());
        $this->assertNull($warehouse->getCountry());
        $this->assertTrue($warehouse->getIsActive());
        $this->assertCount(0, $warehouse->getLocations());
    }

    public function testSettersAndGetters(): void
    {
        $warehouse = new Warehouse();

        $warehouse->setName('Main Warehouse');
        $this->assertSame('Main Warehouse', $warehouse->getName());

        $warehouse->setCode('WH-001');
        $this->assertSame('WH-001', $warehouse->getCode());

        $warehouse->setAddress('123 Main St');
        $this->assertSame('123 Main St', $warehouse->getAddress());

        $warehouse->setCity('Springfield');
        $this->assertSame('Springfield', $warehouse->getCity());

        $warehouse->setState('IL');
        $this->assertSame('IL', $warehouse->getState());

        $warehouse->setPostalCode('62701');
        $this->assertSame('62701', $warehouse->getPostalCode());

        $warehouse->setCountry('US');
        $this->assertSame('US', $warehouse->getCountry());

        $warehouse->setIsActive(false);
        $this->assertFalse($warehouse->getIsActive());
    }

    public function testAddAndRemoveLocation(): void
    {
        $warehouse = new Warehouse();

        $location = new Location();
        $location->setId('loc-1');
        $location->setName('A-01');
        $location->setType('storage');

        $warehouse->addLocation($location);
        $this->assertCount(1, $warehouse->getLocations());

        $warehouse->removeLocation($location);
        $this->assertCount(0, $warehouse->getLocations());
    }

    public function testFindLocationById(): void
    {
        $warehouse = new Warehouse();

        $loc1 = new Location();
        $loc1->setId('loc-1');
        $loc1->setName('A-01');
        $loc1->setType('storage');

        $loc2 = new Location();
        $loc2->setId('loc-2');
        $loc2->setName('RCV-01');
        $loc2->setType('receiving');

        $warehouse->addLocation($loc1);
        $warehouse->addLocation($loc2);

        $found = $warehouse->findLocationById('loc-2');
        $this->assertNotNull($found);
        $this->assertSame('RCV-01', $found->getName());

        $notFound = $warehouse->findLocationById('loc-999');
        $this->assertNull($notFound);
    }

    public function testToArray(): void
    {
        $warehouse = new Warehouse();
        $warehouse->setName('Test WH');
        $warehouse->setCode('TW-001');
        $warehouse->setCity('London');
        $warehouse->setCountry('UK');

        $location = new Location();
        $location->setId('loc-1');
        $location->setName('A-01');
        $location->setType('storage');
        $warehouse->addLocation($location);

        $array = $warehouse->toArray();

        $this->assertSame('Test WH', $array['name']);
        $this->assertSame('TW-001', $array['code']);
        $this->assertSame('London', $array['city']);
        $this->assertSame('UK', $array['country']);
        $this->assertCount(1, $array['locations']);
        $this->assertSame('A-01', $array['locations'][0]['name']);
    }
}
