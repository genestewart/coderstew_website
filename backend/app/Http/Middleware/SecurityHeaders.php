<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add headers in production
        if (app()->environment('production')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            
            // HSTS header for HTTPS
            if ($request->secure()) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            }

            // Content Security Policy
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-eval' cdnjs.cloudflare.com; " .
                   "style-src 'self' 'unsafe-inline' fonts.googleapis.com; " .
                   "font-src 'self' fonts.gstatic.com; " .
                   "img-src 'self' data: https:; " .
                   "connect-src 'self' https:; " .
                   "frame-ancestors 'self'; " .
                   "base-uri 'self'; " .
                   "form-action 'self'";
            
            $response->headers->set('Content-Security-Policy', $csp);

            // Remove server information
            $response->headers->remove('X-Powered-By');
            $response->headers->remove('Server');
        }

        return $response;
    }
}