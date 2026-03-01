<?php

namespace App\Controller;

use App\Service\WarehouseService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/warehouses')]
#[OA\Tag(name: 'Warehouses')]
class WarehouseController extends BaseApiController
{
    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'List all warehouses',
        responses: [
            new OA\Response(response: 200, description: 'List of warehouses'),
        ]
    )]
    public function index(): JsonResponse
    {
        try {
            $warehouses = $this->warehouseService->findAll();
            $data = array_map(fn($w) => $w->toArray(), $warehouses);
            return $this->listResponse($data);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '[a-f0-9]{24}'])]
    #[OA\Get(
        summary: 'Get a warehouse by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Warehouse details'),
            new OA\Response(response: 404, description: 'Warehouse not found'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        try {
            $warehouse = $this->warehouseService->findById($id);
            return $this->successResponse($warehouse->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create a new warehouse',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Main Warehouse'),
                    new OA\Property(property: 'code', type: 'string', example: 'WH-001'),
                    new OA\Property(property: 'address', type: 'string'),
                    new OA\Property(property: 'city', type: 'string'),
                    new OA\Property(property: 'state', type: 'string'),
                    new OA\Property(property: 'postalCode', type: 'string'),
                    new OA\Property(property: 'country', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Warehouse created'),
            new OA\Response(response: 400, description: 'Validation error'),
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $warehouse = $this->warehouseService->create($data);
            return $this->successResponse($warehouse->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['PUT'], requirements: ['id' => '[a-f0-9]{24}'])]
    #[OA\Put(
        summary: 'Update a warehouse',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'code', type: 'string'),
                    new OA\Property(property: 'address', type: 'string'),
                    new OA\Property(property: 'city', type: 'string'),
                    new OA\Property(property: 'state', type: 'string'),
                    new OA\Property(property: 'postalCode', type: 'string'),
                    new OA\Property(property: 'country', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Warehouse updated'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Warehouse not found'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $warehouse = $this->warehouseService->update($id, $data);
            return $this->successResponse($warehouse->toArray());
        } catch (\InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            return $this->errorResponse($e->getMessage(), $status);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '[a-f0-9]{24}'])]
    #[OA\Delete(
        summary: 'Delete a warehouse',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Warehouse deleted'),
            new OA\Response(response: 404, description: 'Warehouse not found'),
        ]
    )]
    public function delete(string $id): JsonResponse
    {
        try {
            $this->warehouseService->delete($id);
            return $this->successResponse(['message' => 'Warehouse deleted']);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    // --- Location endpoints ---

    #[Route('/{warehouseId}/locations', methods: ['POST'])]
    #[OA\Post(
        summary: 'Add a location to a warehouse',
        parameters: [
            new OA\Parameter(name: 'warehouseId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'A-01-01'),
                    new OA\Property(property: 'type', type: 'string', enum: ['storage', 'picking', 'picked', 'receiving']),
                    new OA\Property(property: 'aisle', type: 'string'),
                    new OA\Property(property: 'rack', type: 'string'),
                    new OA\Property(property: 'shelf', type: 'string'),
                    new OA\Property(property: 'bin', type: 'string'),
                    new OA\Property(property: 'capacity', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Location added'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Warehouse not found'),
        ]
    )]
    public function addLocation(Request $request, string $warehouseId): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $location = $this->warehouseService->addLocation($warehouseId, $data);
            return $this->successResponse($location->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            return $this->errorResponse($e->getMessage(), $status);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/{warehouseId}/locations/{locationId}', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Update a location in a warehouse',
        parameters: [
            new OA\Parameter(name: 'warehouseId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'locationId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'type', type: 'string', enum: ['storage', 'picking', 'picked', 'receiving']),
                    new OA\Property(property: 'aisle', type: 'string'),
                    new OA\Property(property: 'rack', type: 'string'),
                    new OA\Property(property: 'shelf', type: 'string'),
                    new OA\Property(property: 'bin', type: 'string'),
                    new OA\Property(property: 'capacity', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Location updated'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Location or warehouse not found'),
        ]
    )]
    public function updateLocation(Request $request, string $warehouseId, string $locationId): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $location = $this->warehouseService->updateLocation($warehouseId, $locationId, $data);
            return $this->successResponse($location->toArray());
        } catch (\InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            return $this->errorResponse($e->getMessage(), $status);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/{warehouseId}/locations/{locationId}', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete a location from a warehouse',
        parameters: [
            new OA\Parameter(name: 'warehouseId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'locationId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Location deleted'),
            new OA\Response(response: 404, description: 'Location or warehouse not found'),
        ]
    )]
    public function deleteLocation(string $warehouseId, string $locationId): JsonResponse
    {
        try {
            $this->warehouseService->deleteLocation($warehouseId, $locationId);
            return $this->successResponse(['message' => 'Location deleted']);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
