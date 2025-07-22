<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Technology;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories and technologies
        $webAppsCategory = ProjectCategory::where('slug', 'web-applications')->first();
        $ecommerceCategory = ProjectCategory::where('slug', 'e-commerce')->first();
        $apiCategory = ProjectCategory::where('slug', 'apis-backends')->first();
        $portfolioCategory = ProjectCategory::where('slug', 'portfolio-personal')->first();

        $laravel = Technology::where('slug', 'laravel')->first();
        $vue = Technology::where('slug', 'vue-js')->first();
        $react = Technology::where('slug', 'react')->first();
        $tailwind = Technology::where('slug', 'tailwind-css')->first();
        $mysql = Technology::where('slug', 'mysql')->first();
        $docker = Technology::where('slug', 'docker')->first();
        $stripe = Technology::where('slug', 'stripe')->first();
        $primevue = Technology::where('slug', 'primevue')->first();
        $typescript = Technology::where('slug', 'typescript')->first();

        $projects = [
            [
                'title' => 'CoderStew Portfolio Website',
                'slug' => 'coderstew-portfolio-website',
                'excerpt' => 'Professional freelance web development studio website built with Laravel 12 and Vue 3.',
                'description' => '<p>A modern, high-performance website for CoderStew showcasing web development services and portfolio projects. Built with Laravel 12 backend API, Vue 3 frontend with TypeScript, and PrimeVue component library.</p><p>Features include automated SSL certificates with Traefik, comprehensive backup system, Docker containerization for Unraid deployment, and integration with Microsoft Bookings for client consultations.</p><p>The website demonstrates modern web development practices including API-first design, security hardening, and production-ready infrastructure.</p>',
                'project_url' => 'https://coderstew.com',
                'repository_url' => 'https://github.com/coderstew/website',
                'status' => 'published',
                'is_featured' => true,
                'project_category_id' => $portfolioCategory?->id,
                'client_name' => 'CoderStew',
                'project_date' => '2025-07-01',
                'completion_date' => '2025-07-21',
                'meta_title' => 'CoderStew Portfolio Website - Laravel & Vue.js',
                'meta_description' => 'Professional portfolio website built with Laravel 12, Vue 3, and modern web technologies.',
                'sort_order' => 1,
                'technologies' => [$laravel, $vue, $primevue, $tailwind, $mysql, $docker, $typescript],
            ],
            [
                'title' => 'E-Commerce Dashboard',
                'slug' => 'e-commerce-dashboard',
                'excerpt' => 'Comprehensive admin dashboard for managing online stores with real-time analytics.',
                'description' => '<p>A powerful e-commerce management dashboard that provides store owners with comprehensive insights and management tools. Built with Laravel backend and React frontend.</p><p>Features include real-time sales analytics, inventory management, customer relationship tools, and integrated payment processing with Stripe.</p><p>The dashboard includes advanced reporting, automated email marketing, and multi-store management capabilities.</p>',
                'project_url' => 'https://demo-ecommerce-dashboard.com',
                'status' => 'published',
                'is_featured' => true,
                'project_category_id' => $ecommerceCategory?->id,
                'client_name' => 'TechStore Inc.',
                'project_date' => '2025-05-15',
                'completion_date' => '2025-06-30',
                'meta_title' => 'E-Commerce Dashboard - React & Laravel',
                'meta_description' => 'Comprehensive e-commerce management dashboard with real-time analytics and inventory management.',
                'sort_order' => 2,
                'technologies' => [$laravel, $react, $tailwind, $mysql, $stripe, $docker],
            ],
            [
                'title' => 'Task Management SaaS',
                'slug' => 'task-management-saas',
                'excerpt' => 'Multi-tenant task and project management platform for teams.',
                'description' => '<p>A comprehensive SaaS platform for task and project management designed for growing teams. Features real-time collaboration, advanced project tracking, and team communication tools.</p><p>Built with a Laravel API backend and Vue.js frontend, the platform supports multiple workspaces, role-based permissions, and integrations with popular productivity tools.</p><p>The system includes time tracking, reporting dashboards, and automated workflow management.</p>',
                'project_url' => 'https://taskhub-demo.com',
                'repository_url' => 'https://github.com/example/taskhub',
                'demo_url' => 'https://demo.taskhub.com',
                'status' => 'published',
                'is_featured' => true,
                'project_category_id' => $webAppsCategory?->id,
                'client_name' => 'TaskHub Solutions',
                'project_date' => '2025-03-01',
                'completion_date' => '2025-05-15',
                'meta_title' => 'Task Management SaaS - Vue.js & Laravel API',
                'meta_description' => 'Multi-tenant task management platform with real-time collaboration and project tracking.',
                'sort_order' => 3,
                'technologies' => [$laravel, $vue, $tailwind, $mysql, $docker],
            ],
            [
                'title' => 'REST API for Mobile App',
                'slug' => 'rest-api-mobile-app',
                'excerpt' => 'Scalable REST API backend for a social media mobile application.',
                'description' => '<p>A robust REST API built with Laravel to support a social media mobile application. The API handles user authentication, content management, real-time messaging, and social interactions.</p><p>Features include JWT authentication, rate limiting, comprehensive documentation with OpenAPI, and optimized database queries for high performance.</p><p>The API is containerized with Docker and includes automated testing, monitoring, and deployment pipelines.</p>',
                'repository_url' => 'https://github.com/example/social-api',
                'status' => 'published',
                'project_category_id' => $apiCategory?->id,
                'client_name' => 'SocialConnect',
                'project_date' => '2025-02-01',
                'completion_date' => '2025-04-15',
                'meta_title' => 'Social Media REST API - Laravel',
                'meta_description' => 'Scalable REST API for social media mobile app with JWT authentication and real-time features.',
                'sort_order' => 4,
                'technologies' => [$laravel, $mysql, $docker],
            ],
            [
                'title' => 'Real Estate Platform',
                'slug' => 'real-estate-platform',
                'excerpt' => 'Comprehensive real estate listing and management platform.',
                'description' => '<p>A full-featured real estate platform connecting buyers, sellers, and agents. The platform includes property listings, advanced search and filtering, virtual tours, and integrated messaging.</p><p>Built with Laravel and Vue.js, the platform features a responsive design, map integration, image galleries, and a comprehensive admin dashboard for property management.</p><p>The system includes lead management, automated email campaigns, and detailed analytics for agents and agencies.</p>',
                'project_url' => 'https://realestate-demo.com',
                'status' => 'published',
                'project_category_id' => $webAppsCategory?->id,
                'client_name' => 'Premier Properties',
                'project_date' => '2025-01-15',
                'completion_date' => '2025-03-30',
                'gallery_images' => [
                    'projects/real-estate/screenshot-1.jpg',
                    'projects/real-estate/screenshot-2.jpg',
                    'projects/real-estate/screenshot-3.jpg'
                ],
                'meta_title' => 'Real Estate Platform - Property Management System',
                'meta_description' => 'Comprehensive real estate platform with listings, search, and agent management tools.',
                'sort_order' => 5,
                'technologies' => [$laravel, $vue, $tailwind, $mysql, $docker],
            ],
        ];

        foreach ($projects as $projectData) {
            $technologies = $projectData['technologies'] ?? [];
            unset($projectData['technologies']);

            $project = Project::create($projectData);

            // Attach technologies
            if (!empty($technologies)) {
                $project->technologies()->attach($technologies);
            }
        }
    }
}