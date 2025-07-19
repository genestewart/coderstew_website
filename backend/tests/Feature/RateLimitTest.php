<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_rate_limiting()
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/posts')->assertStatus(200);
        }

        $this->getJson('/api/posts')->assertStatus(429);
    }
}
