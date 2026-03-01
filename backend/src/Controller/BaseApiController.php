<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseApiController extends AbstractController
{
    protected function getJsonBody(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            throw new \InvalidArgumentException('Request body is empty');
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    protected function successResponse(mixed $data, int $status = 200): JsonResponse
    {
        return new JsonResponse(['success' => true, 'data' => $data], $status);
    }

    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return new JsonResponse(['success' => false, 'error' => $message], $status);
    }

    protected function listResponse(array $items): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $items,
            'count' => count($items),
        ]);
    }
}
