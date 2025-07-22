<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class ContactFormThrottle
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $ip = $request->ip();
        $email = $request->input('email');
        
        // Rate limiting keys
        $ipKey = "contact-form-ip:{$ip}";
        $emailKey = $email ? "contact-form-email:" . md5(strtolower($email)) : null;
        
        // Check IP-based rate limiting (stricter)
        if (RateLimiter::tooManyAttempts($ipKey, 3)) { // 3 attempts per hour per IP
            $seconds = RateLimiter::availableIn($ipKey);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many contact form submissions from your IP address. Please try again later.',
                'retry_after' => $seconds
            ], 429);
        }
        
        // Check email-based rate limiting (if email provided)
        if ($emailKey && RateLimiter::tooManyAttempts($emailKey, 5)) { // 5 attempts per day per email
            $seconds = RateLimiter::availableIn($emailKey);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many submissions with this email address. Please try again later.',
                'retry_after' => $seconds
            ], 429);
        }
        
        // Check for rapid successive submissions (honeypot timing)
        $rapidKey = "contact-form-rapid:{$ip}";
        if (Cache::has($rapidKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait a moment before submitting another message.',
            ], 429);
        }
        
        // Set rapid submission protection (30 seconds)
        Cache::put($rapidKey, true, 30);
        
        // Record the attempts
        RateLimiter::hit($ipKey, 3600); // 1 hour for IP
        if ($emailKey) {
            RateLimiter::hit($emailKey, 86400); // 24 hours for email
        }
        
        return $next($request);
    }
}