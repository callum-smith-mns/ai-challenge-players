<?php

namespace App\Service;

use App\Document\Stock;
use App\Document\StockMovement;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StockService
{
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly ValidatorInterface $validator,
        private readonly WarehouseService $warehouseService,
        private readonly ProductService $productService,
    ) {}

    /**
     * Receive stock into a receiving location.
     */
    public function receiveStock(array $data): StockMovement
    {
        $this->validateRequiredFields($data, ['productId', 'warehouseId', 'locationId', 'quantity']);

        $product = $this->productService->findById($data['productId']);
        $warehouse = $this->warehouseService->findById($data['warehouseId']);
        $location = $warehouse->findLocationById($data['locationId']);

        if (!$location) {
            throw new \InvalidArgumentException('Location not found in this warehouse');
        }

        if ($location->getType() !== 'receiving') {
            throw new \InvalidArgumentException('Stock can only be received into receiving locations');
        }

        $quantity = (int) $data['quantity'];
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $stock = $this->findOrCreateStock(
            $data['productId'],
            $data['warehouseId'],
            $data['locationId'],
            $data['batchNumber'] ?? null,
            isset($data['expiryDate']) ? new \DateTimeImmutable($data['expiryDate']) : null
        );
        $stock->addQuantity($quantity);

        $movement = $this->createMovement(
            StockMovement::TYPE_RECEIVE,
            $data['productId'],
            $data['warehouseId'],
            null,
            $data['locationId'],
            $quantity,
            $data['reference'] ?? null,
            $data['notes'] ?? null,
            $data['batchNumber'] ?? null
        );

        $this->dm->flush();

        return $movement;
    }

    /**
     * Generic stock movement between locations.
     */
    public function moveStock(array $data): StockMovement
    {
        $this->validateRequiredFields($data, ['productId', 'warehouseId', 'fromLocationId', 'toLocationId', 'quantity']);

        $product = $this->productService->findById($data['productId']);
        $warehouse = $this->warehouseService->findById($data['warehouseId']);

        $fromLocation = $warehouse->findLocationById($data['fromLocationId']);
        if (!$fromLocation) {
            throw new \InvalidArgumentException('Source location not found in this warehouse');
        }

        $toLocation = $warehouse->findLocationById($data['toLocationId']);
        if (!$toLocation) {
            throw new \InvalidArgumentException('Destination location not found in this warehouse');
        }

        if ($data['fromLocationId'] === $data['toLocationId']) {
            throw new \InvalidArgumentException('Source and destination locations must be different');
        }

        $quantity = (int) $data['quantity'];
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $sourceStock = $this->findStock($data['productId'], $data['warehouseId'], $data['fromLocationId']);
        if (!$sourceStock || $sourceStock->getQuantity() < $quantity) {
            throw new \InvalidArgumentException('Insufficient stock at source location');
        }

        $sourceStock->removeQuantity($quantity);

        $destStock = $this->findOrCreateStock(
            $data['productId'],
            $data['warehouseId'],
            $data['toLocationId'],
            $sourceStock->getBatchNumber(),
            $sourceStock->getExpiryDate()
        );
        $destStock->addQuantity($quantity);

        $movementType = $data['movementType'] ?? StockMovement::TYPE_TRANSFER;

        $movement = $this->createMovement(
            $movementType,
            $data['productId'],
            $data['warehouseId'],
            $data['fromLocationId'],
            $data['toLocationId'],
            $quantity,
            $data['reference'] ?? null,
            $data['notes'] ?? null,
            $sourceStock->getBatchNumber()
        );

        if ($sourceStock->getQuantity() === 0) {
            $this->dm->remove($sourceStock);
        }

        $this->dm->flush();

        return $movement;
    }

    /**
     * Store stock: move from receiving to storage location.
     */
    public function storeStock(array $data): StockMovement
    {
        $this->validateRequiredFields($data, ['productId', 'warehouseId', 'fromLocationId', 'toLocationId', 'quantity']);

        $warehouse = $this->warehouseService->findById($data['warehouseId']);

        $fromLocation = $warehouse->findLocationById($data['fromLocationId']);
        if (!$fromLocation || $fromLocation->getType() !== 'receiving') {
            throw new \InvalidArgumentException('Source must be a receiving location');
        }

        $toLocation = $warehouse->findLocationById($data['toLocationId']);
        if (!$toLocation || $toLocation->getType() !== 'storage') {
            throw new \InvalidArgumentException('Destination must be a storage location');
        }

        $data['movementType'] = StockMovement::TYPE_STORE;
        return $this->moveStock($data);
    }

    /**
     * Pick stock: move from storage to picking location.
     */
    public function pickStock(array $data): StockMovement
    {
        $this->validateRequiredFields($data, ['productId', 'warehouseId', 'fromLocationId', 'toLocationId', 'quantity']);

        $warehouse = $this->warehouseService->findById($data['warehouseId']);

        $fromLocation = $warehouse->findLocationById($data['fromLocationId']);
        if (!$fromLocation || $fromLocation->getType() !== 'storage') {
            throw new \InvalidArgumentException('Source must be a storage location');
        }

        $toLocation = $warehouse->findLocationById($data['toLocationId']);
        if (!$toLocation || $toLocation->getType() !== 'picking') {
            throw new \InvalidArgumentException('Destination must be a picking location');
        }

        $data['movementType'] = StockMovement::TYPE_PICK;
        return $this->moveStock($data);
    }

    /**
     * Pack stock: move from picking to picked location.
     */
    public function packStock(array $data): StockMovement
    {
        $this->validateRequiredFields($data, ['productId', 'warehouseId', 'fromLocationId', 'toLocationId', 'quantity']);

        $warehouse = $this->warehouseService->findById($data['warehouseId']);

        $fromLocation = $warehouse->findLocationById($data['fromLocationId']);
        if (!$fromLocation || $fromLocation->getType() !== 'picking') {
            throw new \InvalidArgumentException('Source must be a picking location');
        }

        $toLocation = $warehouse->findLocationById($data['toLocationId']);
        if (!$toLocation || $toLocation->getType() !== 'picked') {
            throw new \InvalidArgumentException('Destination must be a picked location');
        }

        $data['movementType'] = StockMovement::TYPE_PACK;
        return $this->moveStock($data);
    }

    /**
     * Ship stock: remove from inventory (from picked location).
     */
    public function shipStock(array $data): StockMovement
    {
        $this->validateRequiredFields($data, ['productId', 'warehouseId', 'fromLocationId', 'quantity']);

        $product = $this->productService->findById($data['productId']);
        $warehouse = $this->warehouseService->findById($data['warehouseId']);

        $fromLocation = $warehouse->findLocationById($data['fromLocationId']);
        if (!$fromLocation || $fromLocation->getType() !== 'picked') {
            throw new \InvalidArgumentException('Stock can only be shipped from picked locations');
        }

        $quantity = (int) $data['quantity'];
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $stock = $this->findStock($data['productId'], $data['warehouseId'], $data['fromLocationId']);
        if (!$stock || $stock->getQuantity() < $quantity) {
            throw new \InvalidArgumentException('Insufficient stock at source location');
        }

        $stock->removeQuantity($quantity);

        $movement = $this->createMovement(
            StockMovement::TYPE_SHIP,
            $data['productId'],
            $data['warehouseId'],
            $data['fromLocationId'],
            null,
            $quantity,
            $data['reference'] ?? null,
            $data['notes'] ?? null,
            $stock->getBatchNumber()
        );

        if ($stock->getQuantity() === 0) {
            $this->dm->remove($stock);
        }

        $this->dm->flush();

        return $movement;
    }

    /**
     * Get all stock entries, optionally filtered.
     */
    public function getStock(array $filters = []): array
    {
        $criteria = [];

        if (!empty($filters['productId'])) {
            $criteria['productId'] = $filters['productId'];
        }
        if (!empty($filters['warehouseId'])) {
            $criteria['warehouseId'] = $filters['warehouseId'];
        }
        if (!empty($filters['locationId'])) {
            $criteria['locationId'] = $filters['locationId'];
        }

        return $this->dm->getRepository(Stock::class)->findBy($criteria);
    }

    /**
     * Get movement log, optionally filtered.
     */
    public function getMovements(array $filters = []): array
    {
        $criteria = [];

        if (!empty($filters['productId'])) {
            $criteria['productId'] = $filters['productId'];
        }
        if (!empty($filters['warehouseId'])) {
            $criteria['warehouseId'] = $filters['warehouseId'];
        }
        if (!empty($filters['movementType'])) {
            $criteria['movementType'] = $filters['movementType'];
        }

        return $this->dm->getRepository(StockMovement::class)->findBy(
            $criteria,
            ['createdAt' => 'desc']
        );
    }

    /**
     * Get dashboard summary data.
     */
    public function getDashboardSummary(): array
    {
        $allStock = $this->dm->getRepository(Stock::class)->findAll();
        $allMovements = $this->dm->getRepository(StockMovement::class)->findBy([], ['createdAt' => 'desc']);

        $totalItems = 0;
        $stockByWarehouse = [];
        $stockByProduct = [];

        foreach ($allStock as $stock) {
            $totalItems += $stock->getQuantity();

            $whId = $stock->getWarehouseId();
            if (!isset($stockByWarehouse[$whId])) {
                $stockByWarehouse[$whId] = 0;
            }
            $stockByWarehouse[$whId] += $stock->getQuantity();

            $pId = $stock->getProductId();
            if (!isset($stockByProduct[$pId])) {
                $stockByProduct[$pId] = 0;
            }
            $stockByProduct[$pId] += $stock->getQuantity();
        }

        $movementsByType = [];
        $recentMovements = [];
        $count = 0;
        foreach ($allMovements as $movement) {
            $type = $movement->getMovementType();
            if (!isset($movementsByType[$type])) {
                $movementsByType[$type] = 0;
            }
            $movementsByType[$type]++;

            if ($count < 10) {
                $recentMovements[] = $movement->toArray();
                $count++;
            }
        }

        return [
            'totalStockItems' => $totalItems,
            'totalProducts' => count($stockByProduct),
            'totalWarehouses' => count($stockByWarehouse),
            'stockByWarehouse' => $stockByWarehouse,
            'stockByProduct' => $stockByProduct,
            'movementsByType' => $movementsByType,
            'recentMovements' => $recentMovements,
            'totalMovements' => count($allMovements),
        ];
    }

    private function findStock(string $productId, string $warehouseId, string $locationId): ?Stock
    {
        return $this->dm->getRepository(Stock::class)->findOneBy([
            'productId' => $productId,
            'warehouseId' => $warehouseId,
            'locationId' => $locationId,
        ]);
    }

    private function findOrCreateStock(
        string $productId,
        string $warehouseId,
        string $locationId,
        ?string $batchNumber = null,
        ?\DateTimeInterface $expiryDate = null,
    ): Stock {
        $stock = $this->findStock($productId, $warehouseId, $locationId);

        if (!$stock) {
            $stock = new Stock();
            $stock->setProductId($productId);
            $stock->setWarehouseId($warehouseId);
            $stock->setLocationId($locationId);
            $stock->setBatchNumber($batchNumber);
            $stock->setExpiryDate($expiryDate);
            $this->dm->persist($stock);
        }

        return $stock;
    }

    private function createMovement(
        string $type,
        string $productId,
        string $warehouseId,
        ?string $fromLocationId,
        ?string $toLocationId,
        int $quantity,
        ?string $reference,
        ?string $notes,
        ?string $batchNumber,
    ): StockMovement {
        $movement = new StockMovement();
        $movement->setMovementType($type);
        $movement->setProductId($productId);
        $movement->setWarehouseId($warehouseId);
        $movement->setFromLocationId($fromLocationId);
        $movement->setToLocationId($toLocationId);
        $movement->setQuantity($quantity);
        $movement->setReference($reference);
        $movement->setNotes($notes);
        $movement->setBatchNumber($batchNumber);

        $errors = $this->validator->validate($movement);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new \InvalidArgumentException(implode('; ', $messages));
        }

        $this->dm->persist($movement);

        return $movement;
    }

    private function validateRequiredFields(array $data, array $fields): void
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException('Missing required fields: ' . implode(', ', $missing));
        }
    }
}
