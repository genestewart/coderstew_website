<?php

namespace Tests\Unit;

use App\Services\ApiResponse;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_successful_response_creation()
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $response = new ApiResponse(true, $data, 200);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->failed());
        $this->assertEquals($data, $response->getData());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->getError());
    }

    public function test_error_response_creation()
    {
        $error = 'Something went wrong';
        $response = new ApiResponse(false, null, 500, $error);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->failed());
        $this->assertNull($response->getData());
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals($error, $response->getError());
    }

    public function test_response_with_meta()
    {
        $meta = ['total' => 100, 'page' => 1];
        $response = new ApiResponse(true, ['items' => []], 200, null, $meta);

        $this->assertEquals($meta, $response->getMeta());
        $this->assertEquals(100, $response->getMeta('total'));
        $this->assertEquals(1, $response->getMeta('page'));
        $this->assertNull($response->getMeta('nonexistent'));
    }

    public function test_to_array_conversion()
    {
        $data = ['test' => 'value'];
        $meta = ['count' => 1];
        $response = new ApiResponse(true, $data, 200, null, $meta);

        $array = $response->toArray();

        $this->assertEquals([
            'success' => true,
            'data' => $data,
            'status_code' => 200,
            'error' => null,
            'meta' => $meta
        ], $array);
    }

    public function test_to_json_conversion()
    {
        $response = new ApiResponse(true, ['test' => 'value'], 200);
        $json = $response->toJson();

        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertEquals(true, $decoded['success']);
        $this->assertEquals(['test' => 'value'], $decoded['data']);
        $this->assertEquals(200, $decoded['status_code']);
    }

    public function test_static_success_method()
    {
        $data = ['id' => 1];
        $meta = ['type' => 'user'];
        $response = ApiResponse::success($data, 201, $meta);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($data, $response->getData());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($meta, $response->getMeta());
        $this->assertNull($response->getError());
    }

    public function test_static_error_method()
    {
        $message = 'Validation failed';
        $meta = ['field' => 'email'];
        $response = ApiResponse::error($message, 422, $meta);

        $this->assertTrue($response->failed());
        $this->assertEquals($message, $response->getError());
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals($meta, $response->getMeta());
        $this->assertNull($response->getData());
    }

    public function test_from_http_response_method()
    {
        $data = ['result' => 'success'];
        $response = ApiResponse::fromHttpResponse(true, $data, 200);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($data, $response->getData());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->getError());
    }

    public function test_from_http_response_with_error()
    {
        $error = 'Not found';
        $response = ApiResponse::fromHttpResponse(false, null, 404, $error);

        $this->assertTrue($response->failed());
        $this->assertNull($response->getData());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals($error, $response->getError());
    }

    public function test_readonly_properties()
    {
        $response = new ApiResponse(true, ['data'], 200, null, ['meta']);

        // Test that properties are readonly by checking they exist
        $this->assertTrue($response->success);
        $this->assertEquals(['data'], $response->data);
        $this->assertEquals(200, $response->statusCode);
        $this->assertNull($response->error);
        $this->assertEquals(['meta'], $response->meta);
    }

    public function test_default_values()
    {
        $response = new ApiResponse(true);

        $this->assertTrue($response->isSuccessful());
        $this->assertNull($response->getData());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->getError());
        $this->assertEquals([], $response->getMeta());
    }
}