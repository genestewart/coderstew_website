<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Project URLs and repository
            $table->string('project_url')->nullable()->after('featured_image');
            $table->string('repository_url')->nullable()->after('project_url');
            $table->string('demo_url')->nullable()->after('repository_url');
            
            // Project metadata
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('demo_url');
            $table->boolean('is_featured')->default(false)->after('status');
            $table->integer('sort_order')->default(0)->after('is_featured');
            
            // Client and project details
            $table->string('client_name')->nullable()->after('sort_order');
            $table->date('project_date')->nullable()->after('client_name');
            $table->date('completion_date')->nullable()->after('project_date');
            
            // Gallery and media
            $table->json('gallery_images')->nullable()->after('completion_date');
            $table->string('video_url')->nullable()->after('gallery_images');
            
            // SEO and metadata
            $table->string('meta_title')->nullable()->after('video_url');
            $table->text('meta_description')->nullable()->after('meta_title');
            
            // Project categories relationship
            $table->foreignId('project_category_id')->nullable()->after('meta_description')->constrained()->nullOnDelete();
            
            // Indexes for performance
            $table->index(['status', 'is_featured']);
            $table->index(['project_date', 'status']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_featured']);
            $table->dropIndex(['project_date', 'status']);
            $table->dropIndex(['sort_order']);
            
            $table->dropForeign(['project_category_id']);
            $table->dropColumn([
                'project_url',
                'repository_url', 
                'demo_url',
                'status',
                'is_featured',
                'sort_order',
                'client_name',
                'project_date',
                'completion_date',
                'gallery_images',
                'video_url',
                'meta_title',
                'meta_description',
                'project_category_id'
            ]);
        });
    }
};