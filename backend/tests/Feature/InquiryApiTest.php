<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_inquiry()
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inquiries', ['email' => 'john@example.com']);
    }
}
