<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class InquiryValidationTest extends TestCase
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
    public function it_accepts_valid_inquiry_data()
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a legitimate inquiry about your services.',
            'subject' => 'Service Inquiry',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Thank you for your message! We will get back to you within 24 hours.',
                ]);

        $this->assertDatabaseHas('inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a legitimate inquiry about your services.',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_requires_all_mandatory_fields()
    {
        $response = $this->postJson('/api/inquiries', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'message']);
    }

    /** @test */
    public function it_validates_name_format()
    {
        // Test invalid name with numbers
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John123',
            'email' => 'john@example.com',
            'message' => 'Test message',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);

        // Test name too short
        $response = $this->postJson('/api/inquiries', [
            'name' => 'J',
            'email' => 'john@example.com',
            'message' => 'Test message',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        // Test invalid email
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'message' => 'Test message',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Test email with suspicious pattern
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'test+spam+here@example.com',
            'message' => 'Test message',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_message_length()
    {
        // Test message too short
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hi',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['message']);

        // Test message too long
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => str_repeat('a', 2001),
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_detects_honeypot_spam()
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message',
            'website' => 'http://spam.com', // Honeypot field should be empty
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['website']);
    }

    /** @test */
    public function it_validates_form_timing()
    {
        // Test form submitted too quickly
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message',
            'form_start_time' => time() - 1, // Only 1 second ago
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['form_start_time']);

        // Test form submitted too long ago
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message',
            'form_start_time' => time() - 3700, // More than 1 hour ago
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['form_start_time']);
    }

    /** @test */
    public function it_detects_spam_keywords()
    {
        $spamMessages = [
            'I want to buy viagra cheap',
            'Best casino games here click now',
            'Make money fast with forex trading',
            'SEO service for your website guaranteed',
        ];

        foreach ($spamMessages as $spamMessage) {
            $response = $this->postJson('/api/inquiries', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'message' => $spamMessage,
                'form_start_time' => time() - 10,
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['message']);
        }
    }

    /** @test */
    public function it_detects_excessive_urls()
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Check out these sites: http://site1.com http://site2.com http://site3.com http://site4.com',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_detects_repeated_characters()
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a message with aaaaaa repeated characters',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_detects_excessive_capitalization()
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'THIS IS A MESSAGE WITH TOO MANY CAPITAL LETTERS WHICH LOOKS LIKE SPAM',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_detects_disposable_email_providers()
    {
        $disposableEmails = [
            'test@10minutemail.com',
            'test@tempmail.org',
            'test@guerrillamail.com',
            'test@mailinator.com',
        ];

        foreach ($disposableEmails as $email) {
            $response = $this->postJson('/api/inquiries', [
                'name' => 'John Doe',
                'email' => $email,
                'message' => 'This is a legitimate message',
                'form_start_time' => time() - 10,
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['email']);
        }
    }

    /** @test */
    public function it_detects_suspicious_email_patterns()
    {
        $suspiciousEmails = [
            'test@123.com', // Numeric domain
            'test@verylongdomainnamethatistoolongtobereal.com', // Very long domain
        ];

        foreach ($suspiciousEmails as $email) {
            $response = $this->postJson('/api/inquiries', [
                'name' => 'John Doe',
                'email' => $email,
                'message' => 'This is a legitimate message',
                'form_start_time' => time() - 10,
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['email']);
        }
    }

    /** @test */
    public function it_trims_and_normalizes_input()
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => '  John Doe  ',
            'email' => '  JOHN@EXAMPLE.COM  ',
            'message' => '  This is a test message  ',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message',
        ]);
    }

    /** @test */
    public function it_stores_additional_metadata()
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message',
            'subject' => 'Test Subject',
            'form_start_time' => time() - 10,
        ]);

        $response->assertStatus(201);

        $inquiry = Inquiry::latest()->first();
        
        $this->assertNotNull($inquiry->ip_address);
        $this->assertNotNull($inquiry->user_agent);
        $this->assertNotNull($inquiry->submitted_at);
        $this->assertEquals('pending', $inquiry->status);
        $this->assertEquals('Test Subject', $inquiry->subject);
    }

    /** @test */
    public function it_calculates_spam_score_correctly()
    {
        // Create inquiry with legitimate content
        $inquiry = Inquiry::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a legitimate inquiry about your services.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        $this->assertEquals(0, $inquiry->calculateSpamScore());

        // Create inquiry with spam content
        $spamInquiry = Inquiry::create([
            'name' => 'Spammer',
            'email' => 'spam@123.com',
            'message' => 'BUY VIAGRA NOW!!! CLICK HERE http://spam1.com http://spam2.com http://spam3.com',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Bot Agent',
        ]);

        $this->assertGreaterThan(50, $spamInquiry->calculateSpamScore());
    }
}