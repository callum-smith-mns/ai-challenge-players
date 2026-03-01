<?php

namespace App\Tests\Unit\Service;

use App\Document\Product;
use App\Service\ProductService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductServiceTest extends TestCase
{
    private DocumentManager&MockObject $dm;
    private ValidatorInterface&MockObject $validator;
    private DocumentRepository&MockObject $repository;
    private ProductService $service;

    protected function setUp(): void
    {
        $this->dm = $this->createMock(DocumentManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->repository = $this->createMock(DocumentRepository::class);

        $this->dm->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->repository);

        $this->service = new ProductService($this->dm, $this->validator);
    }

    public function testFindAllReturnsProducts(): void
    {
        $products = [new Product(), new Product()];
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($products);

        $result = $this->service->findAll();
        $this->assertCount(2, $result);
    }

    public function testFindByIdReturnsProduct(): void
    {
        $product = new Product();
        $product->setName('Test');
        $this->repository->expects($this->once())
            ->method('find')
            ->with('abc123')
            ->willReturn($product);

        $result = $this->service->findById('abc123');
        $this->assertSame('Test', $result->getName());
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product not found');

        $this->service->findById('nonexistent');
    }

    public function testCreateProduct(): void
    {
        $this->repository->method('findOneBy')
            ->willReturn(null);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->dm->expects($this->once())->method('persist');
        $this->dm->expects($this->once())->method('flush');

        $data = [
            'upc' => '012345678901',
            'name' => 'Test Product',
            'brand' => 'TestBrand',
            'category' => 'Dairy',
            'weight' => 500,
            'weightUnit' => 'g',
        ];

        $product = $this->service->create($data);

        $this->assertSame('012345678901', $product->getUpc());
        $this->assertSame('Test Product', $product->getName());
        $this->assertSame('TestBrand', $product->getBrand());
        $this->assertSame('Dairy', $product->getCategory());
        $this->assertSame(500.0, $product->getWeight());
    }

    public function testCreateProductThrowsOnDuplicateUpc(): void
    {
        $existing = new Product();
        $this->repository->method('findOneBy')
            ->with(['upc' => '012345678901'])
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A product with this UPC already exists');

        $this->service->create(['upc' => '012345678901']);
    }

    public function testCreateProductThrowsOnValidationError(): void
    {
        $this->repository->method('findOneBy')
            ->willReturn(null);

        $violation = new ConstraintViolation(
            'UPC is required',
            null,
            [],
            null,
            'upc',
            ''
        );
        $violations = new ConstraintViolationList([$violation]);

        $this->validator->method('validate')
            ->willReturn($violations);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('upc: UPC is required');

        $this->service->create([
            'upc' => '',
            'name' => 'Test',
            'brand' => 'Brand',
            'category' => 'Cat',
            'weight' => 100,
        ]);
    }

    public function testUpdateProduct(): void
    {
        $product = new Product();
        $product->setUpc('012345678901');
        $product->setName('Old Name');

        $this->repository->method('find')
            ->with('abc123')
            ->willReturn($product);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->dm->expects($this->once())->method('flush');

        $result = $this->service->update('abc123', ['name' => 'New Name']);
        $this->assertSame('New Name', $result->getName());
    }

    public function testDeleteProduct(): void
    {
        $product = new Product();
        $this->repository->method('find')
            ->with('abc123')
            ->willReturn($product);

        $this->dm->expects($this->once())->method('remove')->with($product);
        $this->dm->expects($this->once())->method('flush');

        $this->service->delete('abc123');
    }

    public function testFindByUpc(): void
    {
        $product = new Product();
        $product->setUpc('012345678901');

        $this->repository->method('findOneBy')
            ->with(['upc' => '012345678901'])
            ->willReturn($product);

        $result = $this->service->findByUpc('012345678901');
        $this->assertNotNull($result);
        $this->assertSame('012345678901', $result->getUpc());
    }

    public function testCreateProductWithAllFields(): void
    {
        $this->repository->method('findOneBy')
            ->willReturn(null);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->dm->expects($this->once())->method('persist');
        $this->dm->expects($this->once())->method('flush');

        $data = [
            'upc' => '012345678901',
            'ean' => '0123456789012',
            'name' => 'Organic Milk',
            'description' => 'Fresh organic whole milk',
            'brand' => 'Farm Fresh',
            'category' => 'Dairy',
            'weight' => 1000,
            'weightUnit' => 'g',
            'imageUrl' => 'https://example.com/milk.jpg',
            'ingredients' => ['milk'],
            'allergens' => ['lactose'],
            'nutritionalInfo' => ['calories' => 150],
            'storageInstructions' => 'Keep refrigerated',
            'shelfLifeDays' => 7,
            'countryOfOrigin' => 'US',
            'isActive' => true,
        ];

        $product = $this->service->create($data);

        $this->assertSame('0123456789012', $product->getEan());
        $this->assertSame('Fresh organic whole milk', $product->getDescription());
        $this->assertSame(['milk'], $product->getIngredients());
        $this->assertSame(['lactose'], $product->getAllergens());
        $this->assertSame(7, $product->getShelfLifeDays());
    }
}
