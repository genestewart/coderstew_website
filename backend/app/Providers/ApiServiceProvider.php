<?php

namespace App\Providers;

use App\Services\ApiService;
use App\Services\ExternalApiClient;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the base API service
        $this->app->singleton(ApiService::class, function ($app) {
            return new ApiService();
        });

        // Register external API clients for specific services
        $this->app->bind('api.microsoft.bookings', function ($app) {
            return ExternalApiClient::forService('microsoft_bookings');
        });

        $this->app->bind('api.newsletter', function ($app) {
            return ExternalApiClient::forService('newsletter');
        });

        $this->app->bind('api.analytics', function ($app) {
            return ExternalApiClient::forService('analytics');
        });

        // Create aliases for easier dependency injection
        $this->app->alias(ApiService::class, 'api');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure default settings for API services
        if ($this->app->isProduction()) {
            // In production, be more conservative with timeouts and retries
            resolve(ApiService::class)
                ->setTimeout(30)
                ->setRetries(2)
                ->enableCache(600); // 10 minutes cache
        } else {
            // In development, allow longer timeouts and more retries for debugging
            resolve(ApiService::class)
                ->setTimeout(60)
                ->setRetries(3)
                ->enableCache(60); // 1 minute cache for faster development
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ApiService::class,
            'api',
            'api.microsoft.bookings',
            'api.newsletter',
            'api.analytics',
        ];
    }
}