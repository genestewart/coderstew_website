<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->sentence;
        return [
            'title' => $title,
            'slug' => Str::slug($title.'-'.uniqid()),
            'excerpt' => $this->faker->paragraph,
            'body' => $this->faker->paragraph,
            'published_at' => now(),
        ];
    }
}
