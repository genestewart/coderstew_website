<?php

namespace Tests\Feature;

use App\Services\ApiService;
use App\Services\ApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApiService $apiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiService = new ApiService();
    }

    public function test_successful_get_request()
    {
        Http::fake([
            'https://api.example.com/test' => Http::response([
                'data' => 'test response'
            ], 200)
        ]);

        $response = $this->apiService->get('https://api.example.com/test');

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(['data' => 'test response'], $response->getData());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_failed_get_request()
    {
        Http::fake([
            'https://api.example.com/test' => Http::response([], 500)
        ]);

        $response = $this->apiService->get('https://api.example.com/test');

        $this->assertTrue($response->failed());
        $this->assertNotNull($response->getError());
        $this->assertEquals(0, $response->getStatusCode()); // 0 indicates exception
    }

    public function test_successful_post_request()
    {
        Http::fake([
            'https://api.example.com/test' => Http::response([
                'id' => 1,
                'message' => 'created'
            ], 201)
        ]);

        $response = $this->apiService->post('https://api.example.com/test', [
            'name' => 'Test Item'
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(['id' => 1, 'message' => 'created'], $response->getData());
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_caching_get_request()
    {
        Cache::flush();

        Http::fake([
            'https://api.example.com/cached' => Http::response([
                'data' => 'cached response'
            ], 200)
        ]);

        // First request should hit the API
        $response1 = $this->apiService->get('https://api.example.com/cached');
        $this->assertTrue($response1->isSuccessful());

        // Second request should hit the cache
        $response2 = $this->apiService->get('https://api.example.com/cached');
        $this->assertTrue($response2->isSuccessful());
        $this->assertEquals($response1->getData(), $response2->getData());

        // Verify only one HTTP request was made
        Http::assertSentCount(1);
    }

    public function test_cache_disabled()
    {
        Cache::flush();

        Http::fake([
            'https://api.example.com/nocache' => Http::response([
                'data' => 'no cache response'
            ], 200)
        ]);

        $this->apiService->disableCache();

        // Make two requests
        $response1 = $this->apiService->get('https://api.example.com/nocache');
        $response2 = $this->apiService->get('https://api.example.com/nocache');

        $this->assertTrue($response1->isSuccessful());
        $this->assertTrue($response2->isSuccessful());

        // Both requests should have hit the API
        Http::assertSentCount(2);
    }

    public function test_retry_mechanism()
    {
        // First two requests fail, third succeeds
        Http::fake([
            'https://api.example.com/retry' => Http::sequence()
                ->push([], 500)
                ->push([], 500)
                ->push(['data' => 'success after retry'], 200)
        ]);

        $response = $this->apiService->get('https://api.example.com/retry');

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(['data' => 'success after retry'], $response->getData());
        Http::assertSentCount(3);
    }

    public function test_timeout_configuration()
    {
        $service = (new ApiService())->setTimeout(60);
        
        // Use reflection to check the protected property
        $reflection = new \ReflectionClass($service);
        $timeoutProperty = $reflection->getProperty('timeout');
        $timeoutProperty->setAccessible(true);
        
        $this->assertEquals(60, $timeoutProperty->getValue($service));
    }

    public function test_retries_configuration()
    {
        $service = (new ApiService())->setRetries(5);
        
        // Use reflection to check the protected property
        $reflection = new \ReflectionClass($service);
        $retriesProperty = $reflection->getProperty('retries');
        $retriesProperty->setAccessible(true);
        
        $this->assertEquals(5, $retriesProperty->getValue($service));
    }

    public function test_put_request()
    {
        Http::fake([
            'https://api.example.com/test/1' => Http::response([
                'id' => 1,
                'message' => 'updated'
            ], 200)
        ]);

        $response = $this->apiService->put('https://api.example.com/test/1', [
            'name' => 'Updated Item'
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(['id' => 1, 'message' => 'updated'], $response->getData());
    }

    public function test_delete_request()
    {
        Http::fake([
            'https://api.example.com/test/1' => Http::response([], 204)
        ]);

        $response = $this->apiService->delete('https://api.example.com/test/1');

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_clear_cache()
    {
        Cache::flush();

        Http::fake([
            'https://api.example.com/cache-test' => Http::response([
                'data' => 'cache test'
            ], 200)
        ]);

        // Make request to populate cache
        $this->apiService->get('https://api.example.com/cache-test');
        
        // Clear cache
        $this->apiService->clearCache();
        
        // Make another request - should hit API again
        $this->apiService->get('https://api.example.com/cache-test');
        
        Http::assertSentCount(2);
    }

    public function test_cache_enable_with_custom_ttl()
    {
        $service = (new ApiService())->enableCache(600);
        
        // Use reflection to check the protected property
        $reflection = new \ReflectionClass($service);
        $ttlProperty = $reflection->getProperty('defaultCacheTtl');
        $ttlProperty->setAccessible(true);
        
        $this->assertEquals(600, $ttlProperty->getValue($service));
    }
}