<?php

namespace App\Controller;

use App\Service\StockService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/stock')]
#[OA\Tag(name: 'Stock')]
class StockController extends BaseApiController
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get current stock levels',
        parameters: [
            new OA\Parameter(name: 'productId', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'warehouseId', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'locationId', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stock levels'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'productId' => $request->query->get('productId'),
                'warehouseId' => $request->query->get('warehouseId'),
                'locationId' => $request->query->get('locationId'),
            ];

            $stock = $this->stockService->getStock($filters);
            $data = array_map(fn($s) => $s->toArray(), $stock);
            return $this->listResponse($data);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/receive', methods: ['POST'])]
    #[OA\Post(
        summary: 'Receive stock into a receiving location',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['productId', 'warehouseId', 'locationId', 'quantity'],
                properties: [
                    new OA\Property(property: 'productId', type: 'string'),
                    new OA\Property(property: 'warehouseId', type: 'string'),
                    new OA\Property(property: 'locationId', type: 'string', description: 'Must be a receiving location'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                    new OA\Property(property: 'batchNumber', type: 'string'),
                    new OA\Property(property: 'expiryDate', type: 'string', format: 'date'),
                    new OA\Property(property: 'reference', type: 'string'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Stock received'),
            new OA\Response(response: 400, description: 'Validation error'),
        ]
    )]
    public function receive(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $movement = $this->stockService->receiveStock($data);
            return $this->successResponse($movement->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/move', methods: ['POST'])]
    #[OA\Post(
        summary: 'Move stock between locations (generic movement)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['productId', 'warehouseId', 'fromLocationId', 'toLocationId', 'quantity'],
                properties: [
                    new OA\Property(property: 'productId', type: 'string'),
                    new OA\Property(property: 'warehouseId', type: 'string'),
                    new OA\Property(property: 'fromLocationId', type: 'string'),
                    new OA\Property(property: 'toLocationId', type: 'string'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                    new OA\Property(property: 'reference', type: 'string'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Stock moved'),
            new OA\Response(response: 400, description: 'Validation error'),
        ]
    )]
    public function move(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $movement = $this->stockService->moveStock($data);
            return $this->successResponse($movement->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/store', methods: ['POST'])]
    #[OA\Post(
        summary: 'Store stock (move from receiving to storage location)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['productId', 'warehouseId', 'fromLocationId', 'toLocationId', 'quantity'],
                properties: [
                    new OA\Property(property: 'productId', type: 'string'),
                    new OA\Property(property: 'warehouseId', type: 'string'),
                    new OA\Property(property: 'fromLocationId', type: 'string', description: 'Must be a receiving location'),
                    new OA\Property(property: 'toLocationId', type: 'string', description: 'Must be a storage location'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                    new OA\Property(property: 'reference', type: 'string'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Stock stored'),
            new OA\Response(response: 400, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $movement = $this->stockService->storeStock($data);
            return $this->successResponse($movement->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/pick', methods: ['POST'])]
    #[OA\Post(
        summary: 'Pick stock (move from storage to picking location)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['productId', 'warehouseId', 'fromLocationId', 'toLocationId', 'quantity'],
                properties: [
                    new OA\Property(property: 'productId', type: 'string'),
                    new OA\Property(property: 'warehouseId', type: 'string'),
                    new OA\Property(property: 'fromLocationId', type: 'string', description: 'Must be a storage location'),
                    new OA\Property(property: 'toLocationId', type: 'string', description: 'Must be a picking location'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                    new OA\Property(property: 'reference', type: 'string'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Stock picked'),
            new OA\Response(response: 400, description: 'Validation error'),
        ]
    )]
    public function pick(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $movement = $this->stockService->pickStock($data);
            return $this->successResponse($movement->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/pack', methods: ['POST'])]
    #[OA\Post(
        summary: 'Pack stock (move from picking to picked location)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['productId', 'warehouseId', 'fromLocationId', 'toLocationId', 'quantity'],
                properties: [
                    new OA\Property(property: 'productId', type: 'string'),
                    new OA\Property(property: 'warehouseId', type: 'string'),
                    new OA\Property(property: 'fromLocationId', type: 'string', description: 'Must be a picking location'),
                    new OA\Property(property: 'toLocationId', type: 'string', description: 'Must be a picked location'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                    new OA\Property(property: 'reference', type: 'string'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Stock packed'),
            new OA\Response(response: 400, description: 'Validation error'),
        ]
    )]
    public function pack(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $movement = $this->stockService->packStock($data);
            return $this->successResponse($movement->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/ship', methods: ['POST'])]
    #[OA\Post(
        summary: 'Ship stock (remove from inventory)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['productId', 'warehouseId', 'fromLocationId', 'quantity'],
                properties: [
                    new OA\Property(property: 'productId', type: 'string'),
                    new OA\Property(property: 'warehouseId', type: 'string'),
                    new OA\Property(property: 'fromLocationId', type: 'string', description: 'Must be a picked location'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                    new OA\Property(property: 'reference', type: 'string'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Stock shipped'),
            new OA\Response(response: 400, description: 'Validation error'),
        ]
    )]
    public function ship(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $movement = $this->stockService->shipStock($data);
            return $this->successResponse($movement->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/movements', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get stock movement log',
        parameters: [
            new OA\Parameter(name: 'productId', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'warehouseId', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'movementType', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['receive', 'store', 'pick', 'pack', 'ship', 'transfer'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Movement log'),
        ]
    )]
    public function movements(Request $request): JsonResponse
    {
        try {
            $filters = [
                'productId' => $request->query->get('productId'),
                'warehouseId' => $request->query->get('warehouseId'),
                'movementType' => $request->query->get('movementType'),
            ];

            $movements = $this->stockService->getMovements($filters);
            $data = array_map(fn($m) => $m->toArray(), $movements);
            return $this->listResponse($data);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/dashboard', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get dashboard summary',
        responses: [
            new OA\Response(response: 200, description: 'Dashboard summary data'),
        ]
    )]
    public function dashboard(): JsonResponse
    {
        try {
            $summary = $this->stockService->getDashboardSummary();
            return $this->successResponse($summary);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
