<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class ContactFormThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiting for each test
        RateLimiter::clear('contact-form-ip:127.0.0.1');
        RateLimiter::clear('contact-form-rapid:127.0.0.1');
        Cache::flush();
    }

    /** @test */
    public function it_allows_first_submission()
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a legitimate inquiry.',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_throttles_ip_based_submissions()
    {
        // Make 3 successful submissions (IP limit)
        for ($i = 0; $i < 3; $i++) {
            // Clear rapid submission cache between requests
            Cache::forget('contact-form-rapid:127.0.0.1');
            
            $response = $this->postJson('/api/inquiries', [
                'name' => 'John Doe ' . $i,
                'email' => 'john' . $i . '@example.com',
                'message' => 'This is inquiry number ' . $i,
                'form_start_time' => time() - 10,
            ]);

            $response->assertStatus(201);
        }

        // Clear rapid submission cache for final request
        Cache::forget('contact-form-rapid:127.0.0.1');

        // 4th submission should be throttled
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This should be throttled',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'message' => 'Too many contact form submissions from your IP address. Please try again later.',
                ]);
    }

    /** @test */
    public function it_throttles_email_based_submissions()
    {
        $email = 'test@example.com';
        
        // Make 5 successful submissions with the same email (email limit)
        for ($i = 0; $i < 5; $i++) {
            // Clear rapid submission and IP cache to test email-specific throttling
            Cache::forget('contact-form-rapid:127.0.0.1');
            RateLimiter::clear('contact-form-ip:127.0.0.1');
            
            $response = $this->postJson('/api/inquiries', [
                'name' => 'John Doe ' . $i,
                'email' => $email,
                'message' => 'This is inquiry number ' . $i,
                'form_start_time' => time() - 10,
            ]);

            $response->assertStatus(201);
        }

        // Clear other throttles
        Cache::forget('contact-form-rapid:127.0.0.1');
        RateLimiter::clear('contact-form-ip:127.0.0.1');

        // 6th submission with same email should be throttled
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => $email,
            'message' => 'This should be throttled',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'message' => 'Too many submissions with this email address. Please try again later.',
                ]);
    }

    /** @test */
    public function it_prevents_rapid_successive_submissions()
    {
        // First submission
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'First message',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(201);

        // Immediate second submission should be blocked by rapid submission check
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john2@example.com',
            'message' => 'Second message',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'message' => 'Please wait a moment before submitting another message.',
                ]);
    }

    /** @test */
    public function it_includes_retry_after_header()
    {
        // Make 3 submissions to trigger IP throttling
        for ($i = 0; $i < 3; $i++) {
            Cache::forget('contact-form-rapid:127.0.0.1');
            
            $this->postJson('/api/inquiries', [
                'name' => 'John Doe ' . $i,
                'email' => 'john' . $i . '@example.com',
                'message' => 'This is inquiry number ' . $i,
                'form_start_time' => time() - 10,
            ]);
        }

        Cache::forget('contact-form-rapid:127.0.0.1');

        // Next submission should be throttled with retry_after info
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This should be throttled',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(429)
                ->assertJsonStructure(['retry_after']);
    }

    /** @test */
    public function it_allows_different_ips_to_submit()
    {
        // First submission from default IP (127.0.0.1)
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a legitimate inquiry.',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(201);

        // Simulate different IP address
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
                         ->postJson('/api/inquiries', [
                            'name' => 'Jane Doe',
                            'email' => 'jane@example.com',
                            'message' => 'This is another legitimate inquiry.',
                            'form_start_time' => time() - 10,
                         ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_properly_counts_attempts_across_requests()
    {
        // Make 2 submissions
        for ($i = 0; $i < 2; $i++) {
            Cache::forget('contact-form-rapid:127.0.0.1');
            
            $response = $this->postJson('/api/inquiries', [
                'name' => 'John Doe ' . $i,
                'email' => 'john' . $i . '@example.com',
                'message' => 'This is inquiry number ' . $i,
                'form_start_time' => time() - 10,
            ]);

            $response->assertStatus(201);
        }

        // Third submission should still work (under IP limit of 3)
        Cache::forget('contact-form-rapid:127.0.0.1');
        
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Third inquiry',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(201);

        // Fourth submission should be throttled
        Cache::forget('contact-form-rapid:127.0.0.1');
        
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Fourth inquiry - should fail',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function it_handles_missing_email_gracefully()
    {
        // Test that middleware doesn't crash when email is missing
        // (This would be caught by validation, but middleware runs first)
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'message' => 'Message without email',
            'form_start_time' => time() - 10,
        ]);

        // Should pass through middleware but fail at validation
        $response->assertStatus(422); // Validation error, not middleware error
    }

    /** @test */
    public function it_tracks_attempts_correctly_with_mixed_success_and_failures()
    {
        // Make a successful submission
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a legitimate inquiry.',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(201);

        // Make a failed submission (validation error) - this should still count for rate limiting
        Cache::forget('contact-form-rapid:127.0.0.1');
        
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'message' => 'Message with invalid email',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422); // Validation error

        // Make another successful submission - should work (2nd attempt)
        Cache::forget('contact-form-rapid:127.0.0.1');
        
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john2@example.com',
            'message' => 'Second legitimate inquiry.',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(201);
    }
}