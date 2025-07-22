<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class ExternalApiClient extends ApiService
{
    protected $baseUrl;
    protected $apiKey;
    protected $headers = [];

    public function __construct(string $baseUrl = '', string $apiKey = '')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        
        // Set default headers for external APIs
        $this->headers = [
            'User-Agent' => 'CoderStew-Website/1.0',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($apiKey) {
            $this->headers['Authorization'] = "Bearer {$apiKey}";
        }
    }

    /**
     * Make GET request to external API
     */
    public function get(string $endpoint, array $query = [], array $options = []): ApiResponse
    {
        $url = $this->buildUrl($endpoint);
        return parent::get($url, $query, $options);
    }

    /**
     * Make POST request to external API
     */
    public function post(string $endpoint, array $data = [], array $options = []): ApiResponse
    {
        $url = $this->buildUrl($endpoint);
        return parent::post($url, $data, $options);
    }

    /**
     * Make PUT request to external API
     */
    public function put(string $endpoint, array $data = [], array $options = []): ApiResponse
    {
        $url = $this->buildUrl($endpoint);
        return parent::put($url, $data, $options);
    }

    /**
     * Make DELETE request to external API
     */
    public function delete(string $endpoint, array $options = []): ApiResponse
    {
        $url = $this->buildUrl($endpoint);
        return parent::delete($url, $options);
    }

    /**
     * Build full URL from endpoint
     */
    protected function buildUrl(string $endpoint): string
    {
        if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
            return $endpoint;
        }

        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }

    /**
     * Add custom header
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers at once
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Get current headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set API key and update authorization header
     */
    public function setApiKey(string $apiKey, string $type = 'Bearer'): self
    {
        $this->apiKey = $apiKey;
        $this->headers['Authorization'] = "{$type} {$apiKey}";
        return $this;
    }

    /**
     * Override executeRequest to include custom headers
     */
    protected function executeRequest(string $method, string $url, array $options = []): \Illuminate\Http\Client\Response
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retries) {
            try {
                $http = \Illuminate\Support\Facades\Http::withHeaders($this->headers);

                // Apply timeout if specified
                if (isset($options['timeout'])) {
                    $http = $http->timeout($options['timeout']);
                }

                $response = match ($method) {
                    'GET' => $http->get($url, $options['query'] ?? []),
                    'POST' => $http->post($url, $options['json'] ?? []),
                    'PUT' => $http->put($url, $options['json'] ?? []),
                    'DELETE' => $http->delete($url),
                    default => throw new Exception("Unsupported HTTP method: $method")
                };

                if ($response->successful()) {
                    Log::info('External API request successful', [
                        'method' => $method,
                        'url' => $url,
                        'status' => $response->status(),
                        'attempts' => $attempts + 1
                    ]);
                    return $response;
                }

                // Handle specific HTTP error codes
                $this->handleHttpError($response);

            } catch (Exception $e) {
                $attempts++;
                $lastException = $e;

                Log::warning('External API request attempt failed', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempts,
                    'error' => $e->getMessage()
                ]);

                if ($attempts < $this->retries) {
                    usleep($this->retryDelay * 1000);
                    $this->retryDelay *= 2; // Exponential backoff
                }
            }
        }

        throw $lastException;
    }

    /**
     * Handle specific HTTP error responses
     */
    protected function handleHttpError(\Illuminate\Http\Client\Response $response): void
    {
        $status = $response->status();
        $body = $response->body();

        $message = match ($status) {
            401 => 'Unauthorized: Invalid API credentials',
            403 => 'Forbidden: Access denied',
            404 => 'Not Found: Resource does not exist',
            429 => 'Too Many Requests: Rate limit exceeded',
            500 => 'Internal Server Error: External service error',
            502 => 'Bad Gateway: External service unavailable',
            503 => 'Service Unavailable: External service temporarily down',
            default => "HTTP {$status}: {$body}"
        };

        throw new Exception($message);
    }

    /**
     * Create client for specific external service
     */
    public static function forService(string $service): self
    {
        return match ($service) {
            'microsoft_bookings' => new self(
                config('services.microsoft.bookings_url', ''),
                config('services.microsoft.api_key', '')
            ),
            'newsletter' => new self(
                config('services.newsletter.api_url', ''),
                config('services.newsletter.api_key', '')
            ),
            'analytics' => new self(
                config('services.analytics.api_url', ''),
                config('services.analytics.api_key', '')
            ),
            default => new self()
        };
    }
}