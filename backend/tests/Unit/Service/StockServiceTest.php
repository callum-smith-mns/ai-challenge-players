<?php

namespace App\Tests\Unit\Service;

use App\Document\Location;
use App\Document\Product;
use App\Document\Stock;
use App\Document\StockMovement;
use App\Document\Warehouse;
use App\Service\ProductService;
use App\Service\StockService;
use App\Service\WarehouseService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StockServiceTest extends TestCase
{
    private DocumentManager&MockObject $dm;
    private ValidatorInterface&MockObject $validator;
    private WarehouseService&MockObject $warehouseService;
    private ProductService&MockObject $productService;
    private DocumentRepository&MockObject $stockRepo;
    private DocumentRepository&MockObject $movementRepo;
    private StockService $service;

    protected function setUp(): void
    {
        $this->dm = $this->createMock(DocumentManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->warehouseService = $this->createMock(WarehouseService::class);
        $this->productService = $this->createMock(ProductService::class);
        $this->stockRepo = $this->createMock(DocumentRepository::class);
        $this->movementRepo = $this->createMock(DocumentRepository::class);

        $this->dm->method('getRepository')
            ->willReturnCallback(function (string $class) {
                return match ($class) {
                    Stock::class => $this->stockRepo,
                    StockMovement::class => $this->movementRepo,
                    default => $this->createMock(DocumentRepository::class),
                };
            });

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->service = new StockService(
            $this->dm,
            $this->validator,
            $this->warehouseService,
            $this->productService,
        );
    }

    private function createWarehouseWithLocations(): Warehouse
    {
        $warehouse = new Warehouse();
        $warehouse->setName('Test WH');
        $warehouse->setCode('WH-001');

        $receiving = new Location();
        $receiving->setId('rcv-1');
        $receiving->setName('RCV-01');
        $receiving->setType('receiving');
        $warehouse->addLocation($receiving);

        $storage = new Location();
        $storage->setId('stg-1');
        $storage->setName('STG-01');
        $storage->setType('storage');
        $warehouse->addLocation($storage);

        $picking = new Location();
        $picking->setId('pck-1');
        $picking->setName('PCK-01');
        $picking->setType('picking');
        $warehouse->addLocation($picking);

        $picked = new Location();
        $picked->setId('pkd-1');
        $picked->setName('PKD-01');
        $picked->setType('picked');
        $warehouse->addLocation($picked);

        return $warehouse;
    }

    public function testReceiveStock(): void
    {
        $product = new Product();
        $product->setUpc('012345678901');
        $product->setName('Test');
        $product->setBrand('B');
        $product->setCategory('C');
        $product->setWeight(100);

        $warehouse = $this->createWarehouseWithLocations();

        $this->productService->method('findById')
            ->with('prod-1')
            ->willReturn($product);

        $this->warehouseService->method('findById')
            ->with('wh-1')
            ->willReturn($warehouse);

        $this->stockRepo->method('findOneBy')
            ->willReturn(null);

        $this->dm->expects($this->exactly(2))->method('persist'); // stock + movement
        $this->dm->expects($this->once())->method('flush');

        $data = [
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'locationId' => 'rcv-1',
            'quantity' => 100,
            'batchNumber' => 'B001',
        ];

        $movement = $this->service->receiveStock($data);

        $this->assertSame('receive', $movement->getMovementType());
        $this->assertSame(100, $movement->getQuantity());
        $this->assertSame('prod-1', $movement->getProductId());
    }

    public function testReceiveStockThrowsForNonReceivingLocation(): void
    {
        $product = new Product();
        $warehouse = $this->createWarehouseWithLocations();

        $this->productService->method('findById')->willReturn($product);
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock can only be received into receiving locations');

        $this->service->receiveStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'locationId' => 'stg-1',
            'quantity' => 10,
        ]);
    }

    public function testReceiveStockThrowsMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields');

        $this->service->receiveStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
        ]);
    }

    public function testReceiveStockThrowsForInvalidQuantity(): void
    {
        $product = new Product();
        $warehouse = $this->createWarehouseWithLocations();

        $this->productService->method('findById')->willReturn($product);
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be positive');

        $this->service->receiveStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'locationId' => 'rcv-1',
            'quantity' => 0,
        ]);
    }

    public function testMoveStock(): void
    {
        $product = new Product();
        $warehouse = $this->createWarehouseWithLocations();

        $this->productService->method('findById')->willReturn($product);
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $sourceStock = new Stock();
        $sourceStock->setProductId('prod-1');
        $sourceStock->setWarehouseId('wh-1');
        $sourceStock->setLocationId('rcv-1');
        $sourceStock->setQuantity(50);

        $this->stockRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($sourceStock) {
                if ($criteria['locationId'] === 'rcv-1') {
                    return $sourceStock;
                }
                return null;
            });

        $this->dm->expects($this->atLeastOnce())->method('persist');
        $this->dm->expects($this->once())->method('flush');

        $data = [
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'rcv-1',
            'toLocationId' => 'stg-1',
            'quantity' => 20,
        ];

        $movement = $this->service->moveStock($data);

        $this->assertSame('transfer', $movement->getMovementType());
        $this->assertSame(20, $movement->getQuantity());
        $this->assertSame(30, $sourceStock->getQuantity());
    }

    public function testMoveStockThrowsInsufficientStock(): void
    {
        $product = new Product();
        $warehouse = $this->createWarehouseWithLocations();

        $this->productService->method('findById')->willReturn($product);
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $sourceStock = new Stock();
        $sourceStock->setQuantity(5);

        $this->stockRepo->method('findOneBy')
            ->willReturn($sourceStock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock at source location');

        $this->service->moveStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'rcv-1',
            'toLocationId' => 'stg-1',
            'quantity' => 100,
        ]);
    }

    public function testMoveStockThrowsSameLocation(): void
    {
        $product = new Product();
        $warehouse = $this->createWarehouseWithLocations();

        $this->productService->method('findById')->willReturn($product);
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source and destination locations must be different');

        $this->service->moveStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'rcv-1',
            'toLocationId' => 'rcv-1',
            'quantity' => 10,
        ]);
    }

    public function testStoreStockValidatesLocationTypes(): void
    {
        $warehouse = $this->createWarehouseWithLocations();
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a receiving location');

        $this->service->storeStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'stg-1',
            'toLocationId' => 'stg-1',
            'quantity' => 10,
        ]);
    }

    public function testStoreStockValidatesDestinationType(): void
    {
        $warehouse = $this->createWarehouseWithLocations();
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Destination must be a storage location');

        $this->service->storeStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'rcv-1',
            'toLocationId' => 'pck-1',
            'quantity' => 10,
        ]);
    }

    public function testPickStockValidatesSourceMustBeStorage(): void
    {
        $warehouse = $this->createWarehouseWithLocations();
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a storage location');

        $this->service->pickStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'rcv-1',
            'toLocationId' => 'pck-1',
            'quantity' => 10,
        ]);
    }

    public function testPickStockValidatesDestMustBePicking(): void
    {
        $warehouse = $this->createWarehouseWithLocations();
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Destination must be a picking location');

        $this->service->pickStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'stg-1',
            'toLocationId' => 'rcv-1',
            'quantity' => 10,
        ]);
    }

    public function testPackStockValidatesSourceMustBePicking(): void
    {
        $warehouse = $this->createWarehouseWithLocations();
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a picking location');

        $this->service->packStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'stg-1',
            'toLocationId' => 'pkd-1',
            'quantity' => 10,
        ]);
    }

    public function testPackStockValidatesDestMustBePicked(): void
    {
        $warehouse = $this->createWarehouseWithLocations();
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Destination must be a picked location');

        $this->service->packStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'pck-1',
            'toLocationId' => 'stg-1',
            'quantity' => 10,
        ]);
    }

    public function testShipStock(): void
    {
        $product = new Product();
        $warehouse = $this->createWarehouseWithLocations();

        $this->productService->method('findById')->willReturn($product);
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $stock = new Stock();
        $stock->setProductId('prod-1');
        $stock->setWarehouseId('wh-1');
        $stock->setLocationId('pkd-1');
        $stock->setQuantity(50);

        $this->stockRepo->method('findOneBy')
            ->willReturn($stock);

        $this->dm->expects($this->once())->method('persist');
        $this->dm->expects($this->once())->method('flush');

        $movement = $this->service->shipStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'pkd-1',
            'quantity' => 30,
        ]);

        $this->assertSame('ship', $movement->getMovementType());
        $this->assertSame(30, $movement->getQuantity());
        $this->assertSame(20, $stock->getQuantity());
    }

    public function testShipStockThrowsForNonPickedLocation(): void
    {
        $product = new Product();
        $warehouse = $this->createWarehouseWithLocations();

        $this->productService->method('findById')->willReturn($product);
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock can only be shipped from picked locations');

        $this->service->shipStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'stg-1',
            'quantity' => 10,
        ]);
    }

    public function testShipStockRemovesZeroQuantityStock(): void
    {
        $product = new Product();
        $warehouse = $this->createWarehouseWithLocations();

        $this->productService->method('findById')->willReturn($product);
        $this->warehouseService->method('findById')->willReturn($warehouse);

        $stock = new Stock();
        $stock->setProductId('prod-1');
        $stock->setWarehouseId('wh-1');
        $stock->setLocationId('pkd-1');
        $stock->setQuantity(10);

        $this->stockRepo->method('findOneBy')->willReturn($stock);

        $this->dm->expects($this->once())->method('remove')->with($stock);
        $this->dm->expects($this->once())->method('persist');

        $this->service->shipStock([
            'productId' => 'prod-1',
            'warehouseId' => 'wh-1',
            'fromLocationId' => 'pkd-1',
            'quantity' => 10,
        ]);
    }

    public function testGetStock(): void
    {
        $stock = [new Stock(), new Stock()];
        $this->stockRepo->method('findBy')
            ->willReturn($stock);

        $result = $this->service->getStock(['productId' => 'prod-1']);
        $this->assertCount(2, $result);
    }

    public function testGetMovements(): void
    {
        $movements = [new StockMovement()];
        $this->movementRepo->method('findBy')
            ->willReturn($movements);

        $result = $this->service->getMovements(['movementType' => 'receive']);
        $this->assertCount(1, $result);
    }

    public function testGetDashboardSummary(): void
    {
        $stock1 = new Stock();
        $stock1->setProductId('p1');
        $stock1->setWarehouseId('w1');
        $stock1->setLocationId('l1');
        $stock1->setQuantity(100);

        $stock2 = new Stock();
        $stock2->setProductId('p2');
        $stock2->setWarehouseId('w1');
        $stock2->setLocationId('l2');
        $stock2->setQuantity(50);

        $this->stockRepo->method('findAll')
            ->willReturn([$stock1, $stock2]);

        $movement = new StockMovement();
        $movement->setMovementType('receive');
        $movement->setProductId('p1');
        $movement->setWarehouseId('w1');
        $movement->setQuantity(100);

        $this->movementRepo->method('findBy')
            ->willReturn([$movement]);

        $summary = $this->service->getDashboardSummary();

        $this->assertSame(150, $summary['totalStockItems']);
        $this->assertSame(2, $summary['totalProducts']);
        $this->assertSame(1, $summary['totalWarehouses']);
        $this->assertArrayHasKey('movementsByType', $summary);
        $this->assertArrayHasKey('recentMovements', $summary);
        $this->assertSame(1, $summary['totalMovements']);
    }
}
