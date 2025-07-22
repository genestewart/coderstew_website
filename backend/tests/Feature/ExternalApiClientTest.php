<?php

namespace Tests\Feature;

use App\Services\ExternalApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalApiClientTest extends TestCase
{
    use RefreshDatabase;

    protected ExternalApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new ExternalApiClient('https://api.example.com', 'test-api-key');
    }

    public function test_client_initialization()
    {
        $headers = $this->client->getHeaders();
        
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals('Bearer test-api-key', $headers['Authorization']);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertEquals('CoderStew-Website/1.0', $headers['User-Agent']);
    }

    public function test_get_request_with_endpoint()
    {
        Http::fake([
            'https://api.example.com/users' => Http::response([
                'users' => ['user1', 'user2']
            ], 200)
        ]);

        $response = $this->client->get('/users');

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(['users' => ['user1', 'user2']], $response->getData());
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/users' &&
                   $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    public function test_post_request_with_data()
    {
        Http::fake([
            'https://api.example.com/users' => Http::response([
                'id' => 1,
                'name' => 'John Doe'
            ], 201)
        ]);

        $response = $this->client->post('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(201, $response->getStatusCode());
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/users' &&
                   $request->data()['name'] === 'John Doe';
        });
    }

    public function test_full_url_passthrough()
    {
        Http::fake([
            'https://different.api.com/test' => Http::response(['data' => 'test'], 200)
        ]);

        $response = $this->client->get('https://different.api.com/test');

        $this->assertTrue($response->isSuccessful());
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://different.api.com/test';
        });
    }

    public function test_add_custom_header()
    {
        Http::fake([
            'https://api.example.com/test' => Http::response(['data' => 'test'], 200)
        ]);

        $this->client->addHeader('X-Custom-Header', 'custom-value');
        $response = $this->client->get('/test');

        $this->assertTrue($response->isSuccessful());
        
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Custom-Header', 'custom-value');
        });
    }

    public function test_set_multiple_headers()
    {
        Http::fake([
            'https://api.example.com/test' => Http::response(['data' => 'test'], 200)
        ]);

        $this->client->setHeaders([
            'X-Header-1' => 'value1',
            'X-Header-2' => 'value2'
        ]);

        $response = $this->client->get('/test');

        $this->assertTrue($response->isSuccessful());
        
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Header-1', 'value1') &&
                   $request->hasHeader('X-Header-2', 'value2');
        });
    }

    public function test_set_api_key()
    {
        Http::fake([
            'https://api.example.com/test' => Http::response(['data' => 'test'], 200)
        ]);

        $this->client->setApiKey('new-api-key', 'Token');
        $response = $this->client->get('/test');

        $this->assertTrue($response->isSuccessful());
        
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Token new-api-key');
        });
    }

    public function test_http_error_handling()
    {
        Http::fake([
            'https://api.example.com/unauthorized' => Http::response([], 401),
            'https://api.example.com/not-found' => Http::response([], 404),
            'https://api.example.com/rate-limit' => Http::response([], 429),
            'https://api.example.com/server-error' => Http::response([], 500)
        ]);

        $responses = [
            $this->client->get('/unauthorized'),
            $this->client->get('/not-found'),
            $this->client->get('/rate-limit'),
            $this->client->get('/server-error')
        ];

        foreach ($responses as $response) {
            $this->assertTrue($response->failed());
            $this->assertNotNull($response->getError());
        }
    }

    public function test_for_service_factory_method()
    {
        // Set up config values for testing
        config([
            'services.microsoft.bookings_url' => 'https://bookings.microsoft.com/api',
            'services.microsoft.api_key' => 'ms-api-key'
        ]);

        $client = ExternalApiClient::forService('microsoft_bookings');
        
        $this->assertInstanceOf(ExternalApiClient::class, $client);
        
        // Check that the headers include the Microsoft API key
        $headers = $client->getHeaders();
        $this->assertEquals('Bearer ms-api-key', $headers['Authorization']);
    }

    public function test_put_request()
    {
        Http::fake([
            'https://api.example.com/users/1' => Http::response([
                'id' => 1,
                'name' => 'John Updated'
            ], 200)
        ]);

        $response = $this->client->put('/users/1', [
            'name' => 'John Updated'
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(['id' => 1, 'name' => 'John Updated'], $response->getData());
    }

    public function test_delete_request()
    {
        Http::fake([
            'https://api.example.com/users/1' => Http::response([], 204)
        ]);

        $response = $this->client->delete('/users/1');

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_client_without_base_url()
    {
        $client = new ExternalApiClient();
        
        Http::fake([
            'https://full.url.com/test' => Http::response(['data' => 'test'], 200)
        ]);

        $response = $client->get('https://full.url.com/test');

        $this->assertTrue($response->isSuccessful());
    }

    public function test_client_without_api_key()
    {
        $client = new ExternalApiClient('https://api.example.com');
        $headers = $client->getHeaders();
        
        $this->assertArrayNotHasKey('Authorization', $headers);
    }

    public function test_retry_with_exponential_backoff()
    {
        // Mock time to test backoff timing
        Http::fake([
            'https://api.example.com/retry-test' => Http::sequence()
                ->push([], 500)
                ->push([], 500)
                ->push(['success' => true], 200)
        ]);

        $response = $this->client->get('/retry-test');

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(['success' => true], $response->getData());
        
        // Verify 3 requests were made (2 failures + 1 success)
        Http::assertSentCount(3);
    }
}