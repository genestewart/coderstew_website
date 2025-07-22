<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Applications',
                'slug' => 'web-applications',
                'description' => 'Full-stack web applications and platforms',
                'color' => '#3B82F6',
                'sort_order' => 1,
            ],
            [
                'name' => 'E-commerce',
                'slug' => 'e-commerce',
                'description' => 'Online stores and e-commerce solutions',
                'color' => '#10B981',
                'sort_order' => 2,
            ],
            [
                'name' => 'APIs & Backends',
                'slug' => 'apis-backends',
                'description' => 'REST APIs and backend services',
                'color' => '#8B5CF6',
                'sort_order' => 3,
            ],
            [
                'name' => 'Mobile Applications',
                'slug' => 'mobile-applications',
                'description' => 'Mobile apps and responsive web applications',
                'color' => '#F59E0B',
                'sort_order' => 4,
            ],
            [
                'name' => 'Business Tools',
                'slug' => 'business-tools',
                'description' => 'Internal tools and business automation',
                'color' => '#EF4444',
                'sort_order' => 5,
            ],
            [
                'name' => 'Content Management',
                'slug' => 'content-management',
                'description' => 'CMS and content management solutions',
                'color' => '#06B6D4',
                'sort_order' => 6,
            ],
            [
                'name' => 'Portfolio & Personal',
                'slug' => 'portfolio-personal',
                'description' => 'Portfolio websites and personal projects',
                'color' => '#84CC16',
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $category) {
            ProjectCategory::create($category);
        }

        // Create some subcategories
        $webAppsCategory = ProjectCategory::where('slug', 'web-applications')->first();
        $subcategories = [
            [
                'name' => 'SaaS Platforms',
                'slug' => 'saas-platforms',
                'description' => 'Software as a Service platforms',
                'parent_id' => $webAppsCategory->id,
                'color' => '#3B82F6',
                'sort_order' => 1,
            ],
            [
                'name' => 'Dashboards',
                'slug' => 'dashboards',
                'description' => 'Admin dashboards and analytics platforms',
                'parent_id' => $webAppsCategory->id,
                'color' => '#3B82F6',
                'sort_order' => 2,
            ],
        ];

        foreach ($subcategories as $subcategory) {
            ProjectCategory::create($subcategory);
        }
    }
}