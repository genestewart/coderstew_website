<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_index()
    {
        Post::factory()->count(3)->create();

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_post_show()
    {
        $post = Post::factory()->create();

        $response = $this->getJson('/api/posts/'.$post->slug);

        $response->assertStatus(200)->assertJsonFragment(['slug' => $post->slug]);
    }
}
