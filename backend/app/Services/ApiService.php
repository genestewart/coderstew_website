<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class ApiService
{
    protected $timeout = 30;
    protected $retries = 3;
    protected $retryDelay = 1000; // milliseconds
    protected $cacheEnabled = true;
    protected $defaultCacheTtl = 300; // 5 minutes

    /**
     * Make HTTP GET request with error handling and caching
     */
    public function get(string $url, array $query = [], array $options = []): ApiResponse
    {
        $cacheKey = $this->getCacheKey('GET', $url, $query);
        
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            Log::info('API cache hit', ['url' => $url, 'cache_key' => $cacheKey]);
            return new ApiResponse(true, Cache::get($cacheKey), 200);
        }

        try {
            $response = $this->executeRequest('GET', $url, [
                'query' => $query,
                'timeout' => $options['timeout'] ?? $this->timeout,
            ]);

            $data = $this->processResponse($response);
            
            if ($this->cacheEnabled) {
                $ttl = $options['cache_ttl'] ?? $this->defaultCacheTtl;
                Cache::put($cacheKey, $data, $ttl);
            }

            return new ApiResponse(true, $data, $response->status());

        } catch (Exception $e) {
            Log::error('API GET request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new ApiResponse(false, null, 0, $e->getMessage());
        }
    }

    /**
     * Make HTTP POST request with error handling
     */
    public function post(string $url, array $data = [], array $options = []): ApiResponse
    {
        try {
            $response = $this->executeRequest('POST', $url, [
                'json' => $data,
                'timeout' => $options['timeout'] ?? $this->timeout,
            ]);

            $responseData = $this->processResponse($response);

            return new ApiResponse(true, $responseData, $response->status());

        } catch (Exception $e) {
            Log::error('API POST request failed', [
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new ApiResponse(false, null, 0, $e->getMessage());
        }
    }

    /**
     * Make HTTP PUT request with error handling
     */
    public function put(string $url, array $data = [], array $options = []): ApiResponse
    {
        try {
            $response = $this->executeRequest('PUT', $url, [
                'json' => $data,
                'timeout' => $options['timeout'] ?? $this->timeout,
            ]);

            $responseData = $this->processResponse($response);

            return new ApiResponse(true, $responseData, $response->status());

        } catch (Exception $e) {
            Log::error('API PUT request failed', [
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new ApiResponse(false, null, 0, $e->getMessage());
        }
    }

    /**
     * Make HTTP DELETE request with error handling
     */
    public function delete(string $url, array $options = []): ApiResponse
    {
        try {
            $response = $this->executeRequest('DELETE', $url, [
                'timeout' => $options['timeout'] ?? $this->timeout,
            ]);

            $responseData = $this->processResponse($response);

            return new ApiResponse(true, $responseData, $response->status());

        } catch (Exception $e) {
            Log::error('API DELETE request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new ApiResponse(false, null, 0, $e->getMessage());
        }
    }

    /**
     * Execute HTTP request with retries
     */
    protected function executeRequest(string $method, string $url, array $options = []): Response
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retries) {
            try {
                $http = Http::withHeaders([
                    'User-Agent' => 'CoderStew-Website/1.0',
                    'Accept' => 'application/json',
                ]);

                $response = match ($method) {
                    'GET' => $http->get($url, $options['query'] ?? []),
                    'POST' => $http->post($url, $options['json'] ?? []),
                    'PUT' => $http->put($url, $options['json'] ?? []),
                    'DELETE' => $http->delete($url),
                    default => throw new Exception("Unsupported HTTP method: $method")
                };

                if ($response->successful()) {
                    Log::info('API request successful', [
                        'method' => $method,
                        'url' => $url,
                        'status' => $response->status(),
                        'attempts' => $attempts + 1
                    ]);
                    return $response;
                }

                throw new Exception("HTTP {$response->status()}: {$response->body()}");

            } catch (Exception $e) {
                $attempts++;
                $lastException = $e;

                Log::warning('API request attempt failed', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempts,
                    'error' => $e->getMessage()
                ]);

                if ($attempts < $this->retries) {
                    usleep($this->retryDelay * 1000); // Convert to microseconds
                    $this->retryDelay *= 2; // Exponential backoff
                }
            }
        }

        throw $lastException;
    }

    /**
     * Process HTTP response and extract data
     */
    protected function processResponse(Response $response): mixed
    {
        $contentType = $response->header('Content-Type');
        
        if (str_contains($contentType, 'application/json')) {
            return $response->json();
        }
        
        if (str_contains($contentType, 'text/')) {
            return $response->body();
        }
        
        return $response->body();
    }

    /**
     * Generate cache key for request
     */
    protected function getCacheKey(string $method, string $url, array $params = []): string
    {
        return 'api_' . md5($method . $url . serialize($params));
    }

    /**
     * Clear cache for specific URL pattern
     */
    public function clearCache(string $pattern = '*'): void
    {
        $store = Cache::getStore();
        
        // For Redis store, use pattern-based clearing
        if (method_exists($store, 'getRedis')) {
            try {
                $keys = $store->getRedis()->keys("*api_*{$pattern}*");
                
                foreach ($keys as $key) {
                    Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                }
            } catch (\Exception $e) {
                Log::warning('Failed to clear cache with pattern', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // For other stores (like array), flush all cache
            // This is less efficient but works for testing/development
            Cache::flush();
        }
        
        Log::info('API cache cleared', ['pattern' => $pattern]);
    }

    /**
     * Configure service settings
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function setRetries(int $retries): self
    {
        $this->retries = $retries;
        return $this;
    }

    public function disableCache(): self
    {
        $this->cacheEnabled = false;
        return $this;
    }

    public function enableCache(int $ttl = null): self
    {
        $this->cacheEnabled = true;
        if ($ttl !== null) {
            $this->defaultCacheTtl = $ttl;
        }
        return $this;
    }
}