<?php

namespace App\Tests\Unit\Controller;

use App\Controller\BaseApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ConcreteApiController extends BaseApiController
{
    public function testGetJsonBody(Request $request): array
    {
        return $this->getJsonBody($request);
    }

    public function testSuccessResponse(mixed $data, int $status = 200): JsonResponse
    {
        return $this->successResponse($data, $status);
    }

    public function testErrorResponse(string $message, int $status = 400): JsonResponse
    {
        return $this->errorResponse($message, $status);
    }

    public function testListResponse(array $items): JsonResponse
    {
        return $this->listResponse($items);
    }
}
