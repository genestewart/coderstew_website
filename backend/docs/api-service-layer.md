# API Service Layer Documentation

## Overview

The API Service Layer provides a robust, standardized approach to handling HTTP requests, both internal and external API calls, with comprehensive error handling, caching, and retry mechanisms.

## Components

### 1. ApiService Class
**Location**: `app/Services/ApiService.php`

Core service for making HTTP requests with built-in error handling, caching, and retry logic.

#### Features:
- ✅ HTTP methods: GET, POST, PUT, DELETE
- ✅ Automatic retry with exponential backoff
- ✅ Response caching with configurable TTL
- ✅ Request/response logging
- ✅ Configurable timeouts
- ✅ Standardized error handling

#### Usage:
```php
$apiService = app(ApiService::class);

// GET request with caching
$response = $apiService->get('https://api.example.com/users');

// POST request with data
$response = $apiService->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Configure service
$apiService->setTimeout(60)
          ->setRetries(3)
          ->enableCache(300);
```

### 2. ApiResponse Class
**Location**: `app/Services/ApiResponse.php`

Standardized response object for all API interactions.

#### Properties:
- `success`: Boolean indicating success/failure
- `data`: Response data
- `statusCode`: HTTP status code
- `error`: Error message if failed
- `meta`: Additional metadata

#### Usage:
```php
if ($response->isSuccessful()) {
    $data = $response->getData();
} else {
    $error = $response->getError();
}
```

### 3. ExternalApiClient Class
**Location**: `app/Services/ExternalApiClient.php`

Specialized client for external API integrations with authentication and header management.

#### Features:
- ✅ Base URL configuration
- ✅ API key authentication
- ✅ Custom header management
- ✅ Service-specific factory methods
- ✅ Enhanced error handling for common HTTP errors

#### Usage:
```php
// Create client for specific service
$client = ExternalApiClient::forService('microsoft_bookings');

// Or create custom client
$client = new ExternalApiClient('https://api.service.com', 'api-key');

// Add custom headers
$client->addHeader('X-Custom-Header', 'value');

// Make requests
$response = $client->get('/endpoint');
```

### 4. ApiErrorHandler Middleware
**Location**: `app/Http/Middleware/ApiErrorHandler.php`

Comprehensive error handling middleware for API routes.

#### Features:
- ✅ Validation error formatting
- ✅ Model not found handling
- ✅ HTTP error standardization
- ✅ Production-safe error messages
- ✅ Detailed logging

### 5. ApiServiceProvider
**Location**: `app/Providers/ApiServiceProvider.php`

Service provider for registering API services and configuring defaults.

#### Registered Services:
- `ApiService` - Base API service
- `api.microsoft.bookings` - Microsoft Bookings client
- `api.newsletter` - Newsletter service client
- `api.analytics` - Analytics service client

## Configuration

### Environment-based Settings:
- **Production**: 30s timeout, 2 retries, 10min cache
- **Development**: 60s timeout, 3 retries, 1min cache

### Service Configuration:
Add to `config/services.php`:
```php
'microsoft' => [
    'bookings_url' => env('MICROSOFT_BOOKINGS_URL'),
    'api_key' => env('MICROSOFT_API_KEY'),
],
'newsletter' => [
    'api_url' => env('NEWSLETTER_API_URL'),
    'api_key' => env('NEWSLETTER_API_KEY'),
],
```

## Error Handling

### Response Format:
```json
{
    "success": false,
    "error": "Validation failed",
    "message": "The given data was invalid",
    "status_code": 422,
    "errors": {
        "email": ["The email field is required."]
    }
}
```

### Common Error Types:
- **ValidationException**: 422 with field errors
- **ModelNotFoundException**: 404 with resource details
- **HTTP Errors**: Standardized error messages
- **Generic Exceptions**: Safe production messages

## Testing

### Test Coverage:
- ✅ `ApiServiceTest`: Core service functionality
- ✅ `ApiResponseTest`: Response object behavior
- ✅ `ExternalApiClientTest`: External API client features

### Running Tests:
```bash
php artisan test --filter=ApiServiceTest
php artisan test --filter=ApiResponseTest
```

## Caching Strategy

### Cache Keys:
Format: `api_{hash(method+url+params)}`

### Cache Clearing:
```php
// Clear specific pattern (Redis only)
$apiService->clearCache('users*');

// Clear all API cache
$apiService->clearCache();
```

## Logging

All API requests and errors are logged with:
- Request method and URL
- Response status and timing
- Error details and stack traces
- Retry attempts

## Best Practices

1. **Use dependency injection** for ApiService
2. **Check response success** before accessing data
3. **Handle errors gracefully** in controllers
4. **Configure timeouts** based on use case
5. **Use caching** for frequently accessed data
6. **Monitor logs** for API issues

## Integration Examples

### Controller Usage:
```php
class ApiController extends Controller
{
    public function __construct(
        private ApiService $apiService
    ) {}

    public function fetchUsers()
    {
        $response = $this->apiService->get('https://api.example.com/users');
        
        if ($response->failed()) {
            return ApiErrorHandler::errorResponse(
                'External API Error',
                $response->getError(),
                500
            );
        }
        
        return ApiErrorHandler::successResponse($response->getData());
    }
}
```

### Service Integration:
```php
class BookingService
{
    public function __construct(
        private ExternalApiClient $bookingsClient
    ) {
        $this->bookingsClient = app('api.microsoft.bookings');
    }

    public function createAppointment(array $data)
    {
        return $this->bookingsClient->post('/appointments', $data);
    }
}
```