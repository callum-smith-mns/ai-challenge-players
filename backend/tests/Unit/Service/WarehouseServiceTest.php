<?php

namespace App\Tests\Unit\Service;

use App\Document\Location;
use App\Document\Warehouse;
use App\Service\WarehouseService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WarehouseServiceTest extends TestCase
{
    private DocumentManager&MockObject $dm;
    private ValidatorInterface&MockObject $validator;
    private DocumentRepository&MockObject $repository;
    private WarehouseService $service;

    protected function setUp(): void
    {
        $this->dm = $this->createMock(DocumentManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->repository = $this->createMock(DocumentRepository::class);

        $this->dm->method('getRepository')
            ->with(Warehouse::class)
            ->willReturn($this->repository);

        $this->service = new WarehouseService($this->dm, $this->validator);
    }

    public function testFindAllReturnsWarehouses(): void
    {
        $warehouses = [new Warehouse(), new Warehouse()];
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($warehouses);

        $result = $this->service->findAll();
        $this->assertCount(2, $result);
    }

    public function testFindByIdReturnsWarehouse(): void
    {
        $warehouse = new Warehouse();
        $warehouse->setName('Test WH');
        $this->repository->method('find')
            ->with('wh123')
            ->willReturn($warehouse);

        $result = $this->service->findById('wh123');
        $this->assertSame('Test WH', $result->getName());
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $this->repository->method('find')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Warehouse not found');

        $this->service->findById('nonexistent');
    }

    public function testCreateWarehouse(): void
    {
        $this->repository->method('findOneBy')
            ->willReturn(null);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->dm->expects($this->once())->method('persist');
        $this->dm->expects($this->once())->method('flush');

        $data = [
            'name' => 'Main Warehouse',
            'code' => 'WH-001',
            'city' => 'London',
            'country' => 'UK',
        ];

        $warehouse = $this->service->create($data);

        $this->assertSame('Main Warehouse', $warehouse->getName());
        $this->assertSame('WH-001', $warehouse->getCode());
        $this->assertSame('London', $warehouse->getCity());
    }

    public function testCreateWarehouseThrowsOnDuplicateCode(): void
    {
        $existing = new Warehouse();
        $this->repository->method('findOneBy')
            ->with(['code' => 'WH-001'])
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A warehouse with this code already exists');

        $this->service->create(['code' => 'WH-001', 'name' => 'Test']);
    }

    public function testUpdateWarehouse(): void
    {
        $warehouse = new Warehouse();
        $warehouse->setName('Old Name');
        $warehouse->setCode('WH-001');

        $this->repository->method('find')
            ->with('wh123')
            ->willReturn($warehouse);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->dm->expects($this->once())->method('flush');

        $result = $this->service->update('wh123', ['name' => 'New Name']);
        $this->assertSame('New Name', $result->getName());
    }

    public function testDeleteWarehouse(): void
    {
        $warehouse = new Warehouse();
        $this->repository->method('find')
            ->with('wh123')
            ->willReturn($warehouse);

        $this->dm->expects($this->once())->method('remove')->with($warehouse);
        $this->dm->expects($this->once())->method('flush');

        $this->service->delete('wh123');
    }

    public function testAddLocation(): void
    {
        $warehouse = new Warehouse();
        $warehouse->setName('Test WH');

        $this->repository->method('find')
            ->with('wh123')
            ->willReturn($warehouse);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->dm->expects($this->once())->method('flush');

        $data = [
            'name' => 'A-01-01',
            'type' => 'storage',
            'aisle' => 'A',
            'rack' => '01',
            'shelf' => '01',
        ];

        $location = $this->service->addLocation('wh123', $data);

        $this->assertSame('A-01-01', $location->getName());
        $this->assertSame('storage', $location->getType());
        $this->assertNotNull($location->getId());
        $this->assertCount(1, $warehouse->getLocations());
    }

    public function testUpdateLocation(): void
    {
        $warehouse = new Warehouse();
        $location = new Location();
        $location->setId('loc-1');
        $location->setName('Old Name');
        $location->setType('storage');
        $warehouse->addLocation($location);

        $this->repository->method('find')
            ->with('wh123')
            ->willReturn($warehouse);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->dm->expects($this->once())->method('flush');

        $result = $this->service->updateLocation('wh123', 'loc-1', ['name' => 'New Name']);
        $this->assertSame('New Name', $result->getName());
    }

    public function testUpdateLocationThrowsWhenNotFound(): void
    {
        $warehouse = new Warehouse();
        $this->repository->method('find')
            ->with('wh123')
            ->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Location not found');

        $this->service->updateLocation('wh123', 'nonexistent', ['name' => 'X']);
    }

    public function testDeleteLocation(): void
    {
        $warehouse = new Warehouse();
        $location = new Location();
        $location->setId('loc-1');
        $location->setName('A-01');
        $location->setType('storage');
        $warehouse->addLocation($location);

        $this->repository->method('find')
            ->with('wh123')
            ->willReturn($warehouse);

        $this->dm->expects($this->once())->method('flush');

        $this->service->deleteLocation('wh123', 'loc-1');
        $this->assertCount(0, $warehouse->getLocations());
    }

    public function testDeleteLocationThrowsWhenNotFound(): void
    {
        $warehouse = new Warehouse();
        $this->repository->method('find')
            ->with('wh123')
            ->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Location not found');

        $this->service->deleteLocation('wh123', 'nonexistent');
    }

    public function testGetLocation(): void
    {
        $warehouse = new Warehouse();
        $location = new Location();
        $location->setId('loc-1');
        $location->setName('A-01');
        $location->setType('storage');
        $warehouse->addLocation($location);

        $this->repository->method('find')
            ->with('wh123')
            ->willReturn($warehouse);

        $result = $this->service->getLocation('wh123', 'loc-1');
        $this->assertSame('A-01', $result->getName());
    }
}
