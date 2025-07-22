<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => App\Http\Middleware\Authenticate::class,
            'throttle' => Illuminate\Routing\Middleware\ThrottleRequests::class,
            'api.error' => App\Http\Middleware\ApiErrorHandler::class,
            'security.headers' => App\Http\Middleware\SecurityHeaders::class,
            'contact.throttle' => App\Http\Middleware\ContactFormThrottle::class,
        ]);

        $middleware->group('web', [
            Illuminate\Cookie\Middleware\EncryptCookies::class,
            Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Illuminate\Session\Middleware\StartSession::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
            Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
            'security.headers',
        ]);

        $middleware->group('api', [
            Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:60,1',
            Illuminate\Routing\Middleware\SubstituteBindings::class,
            'api.error',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
