<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe()
    {
        $response = $this->postJson('/api/subscribe', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'john@example.com']);
    }
}
