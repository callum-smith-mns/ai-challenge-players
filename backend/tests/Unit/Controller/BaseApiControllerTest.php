<?php

namespace App\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BaseApiControllerTest extends TestCase
{
    private ConcreteApiController $controller;

    protected function setUp(): void
    {
        $this->controller = new ConcreteApiController();
    }

    public function testGetJsonBodyParsesJson(): void
    {
        $request = new Request([], [], [], [], [], [], '{"name": "test"}');
        $result = $this->controller->testGetJsonBody($request);
        $this->assertSame(['name' => 'test'], $result);
    }

    public function testGetJsonBodyThrowsOnEmptyBody(): void
    {
        $request = new Request([], [], [], [], [], [], '');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Request body is empty');
        $this->controller->testGetJsonBody($request);
    }

    public function testGetJsonBodyThrowsOnInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'not-json');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');
        $this->controller->testGetJsonBody($request);
    }

    public function testSuccessResponse(): void
    {
        $response = $this->controller->testSuccessResponse(['id' => 1], 201);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(['id' => 1], $data['data']);
    }

    public function testErrorResponse(): void
    {
        $response = $this->controller->testErrorResponse('Something failed', 400);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('Something failed', $data['error']);
    }

    public function testListResponse(): void
    {
        $items = [['id' => 1], ['id' => 2]];
        $response = $this->controller->testListResponse($items);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(2, $data['count']);
        $this->assertCount(2, $data['data']);
    }
}
