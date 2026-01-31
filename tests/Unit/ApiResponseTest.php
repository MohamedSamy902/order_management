<?php

namespace Tests\Unit;

use App\Helpers\ApiResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_returns_correct_structure(): void
    {
        $response = ApiResponse::success(['key' => 'value'], 'Success message');

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Success message', $content['message']);
        $this->assertEquals(['key' => 'value'], $content['data']);
    }

    public function test_error_returns_correct_structure(): void
    {
        $response = ApiResponse::error('Error message', ['field' => 'error'], 400);

        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Error message', $content['message']);
        $this->assertEquals(['field' => 'error'], $content['errors']);
    }

    public function test_created_returns_201_status(): void
    {
        $response = ApiResponse::created(['id' => 1], 'Created successfully');

        $this->assertEquals(201, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Created successfully', $content['message']);
    }

    public function test_unauthorized_returns_401_status(): void
    {
        $response = ApiResponse::unauthorized('Unauthorized access');

        $this->assertEquals(401, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Unauthorized access', $content['message']);
    }

    public function test_forbidden_returns_403_status(): void
    {
        $response = ApiResponse::forbidden('Forbidden action');

        $this->assertEquals(403, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
    }

    public function test_bad_request_returns_400_status(): void
    {
        $response = ApiResponse::badRequest('Bad request');

        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Bad request', $content['message']);
    }

    public function test_validation_error_returns_422_status(): void
    {
        $errors = ['email' => ['Email is required']];
        $response = ApiResponse::validationError($errors, 'Validation failed');

        $this->assertEquals(422, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals($errors, $content['errors']);
    }

    public function test_no_content_returns_200_status(): void
    {
        $response = ApiResponse::noContent('Deleted successfully');

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertNull($content['data']);
    }

    public function test_success_with_null_data(): void
    {
        $response = ApiResponse::success(null, 'No data');

        $content = json_decode($response->getContent(), true);
        $this->assertNull($content['data']);
    }

    public function test_error_without_errors_array(): void
    {
        $response = ApiResponse::error('Simple error');

        $content = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('errors', $content);
    }
}
