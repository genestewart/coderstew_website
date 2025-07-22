<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Technology;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()
            ->with(['category', 'technologies'])
            ->published()
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($categoryId = $request->get('category')) {
            $query->where('project_category_id', $categoryId);
        }

        // Filter by technology
        if ($technologyId = $request->get('technology')) {
            $query->whereHas('technologies', function ($q) use ($technologyId) {
                $q->where('technologies.id', $technologyId);
            });
        }

        // Filter by featured
        if ($request->has('featured') && $request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Pagination
        $perPage = min($request->get('per_page', 12), 50); // Max 50 items per page
        $projects = $query->paginate($perPage);

        return ProjectResource::collection($projects);
    }

    public function show(Project $project)
    {
        $project->load(['category', 'technologies']);
        return new ProjectResource($project);
    }

    public function filters()
    {
        return response()->json([
            'categories' => ProjectCategory::orderBy('name')->get(['id', 'name', 'slug']),
            'technologies' => Technology::orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }
}
