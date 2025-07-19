<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_index()
    {
        Project::factory()->count(2)->create();

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)->assertJsonCount(2);
    }

    public function test_project_show()
    {
        $project = Project::factory()->create();

        $response = $this->getJson('/api/projects/'.$project->slug);

        $response->assertStatus(200)->assertJsonFragment(['slug' => $project->slug]);
    }
}
