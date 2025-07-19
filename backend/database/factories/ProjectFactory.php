<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $title = $this->faker->sentence;
        return [
            'title' => $title,
            'slug' => Str::slug($title.'-'.uniqid()),
            'excerpt' => $this->faker->paragraph,
            'description' => $this->faker->paragraph,
            'featured_image' => null,
        ];
    }
}
