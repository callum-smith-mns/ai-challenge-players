<?php

namespace App\Controller;

use App\Service\ProductService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products')]
#[OA\Tag(name: 'Products')]
class ProductController extends BaseApiController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'List all products',
        responses: [
            new OA\Response(response: 200, description: 'List of products'),
        ]
    )]
    public function index(): JsonResponse
    {
        try {
            $products = $this->productService->findAll();
            $data = array_map(fn($p) => $p->toArray(), $products);
            return $this->listResponse($data);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get a product by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product details'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->productService->findById($id);
            return $this->successResponse($product->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create a new product',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['upc', 'name', 'brand', 'category', 'weight'],
                properties: [
                    new OA\Property(property: 'upc', type: 'string', example: '012345678901'),
                    new OA\Property(property: 'ean', type: 'string', example: '0123456789012'),
                    new OA\Property(property: 'name', type: 'string', example: 'Organic Whole Milk'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'brand', type: 'string', example: 'Farm Fresh'),
                    new OA\Property(property: 'category', type: 'string', example: 'Dairy'),
                    new OA\Property(property: 'weight', type: 'number', example: 1000),
                    new OA\Property(property: 'weightUnit', type: 'string', enum: ['g', 'kg', 'oz', 'lb']),
                    new OA\Property(property: 'imageUrl', type: 'string'),
                    new OA\Property(property: 'ingredients', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'allergens', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'nutritionalInfo', type: 'object'),
                    new OA\Property(property: 'storageInstructions', type: 'string'),
                    new OA\Property(property: 'shelfLifeDays', type: 'integer'),
                    new OA\Property(property: 'countryOfOrigin', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Product created'),
            new OA\Response(response: 400, description: 'Validation error'),
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $product = $this->productService->create($data);
            return $this->successResponse($product->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Update a product',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'upc', type: 'string'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'brand', type: 'string'),
                    new OA\Property(property: 'category', type: 'string'),
                    new OA\Property(property: 'weight', type: 'number'),
                    new OA\Property(property: 'weightUnit', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product updated'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $data = $this->getJsonBody($request);
            $product = $this->productService->update($id, $data);
            return $this->successResponse($product->toArray());
        } catch (\InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            return $this->errorResponse($e->getMessage(), $status);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete a product',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product deleted'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function delete(string $id): JsonResponse
    {
        try {
            $this->productService->delete($id);
            return $this->successResponse(['message' => 'Product deleted']);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
