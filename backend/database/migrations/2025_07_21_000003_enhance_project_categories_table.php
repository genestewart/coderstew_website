<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_categories', function (Blueprint $table) {
            // Category details
            $table->text('description')->nullable()->after('slug');
            $table->string('icon')->nullable()->after('description'); // Icon for category
            $table->string('color')->nullable()->after('icon'); // Category color
            
            // Hierarchy support
            $table->foreignId('parent_id')->nullable()->after('color')->constrained('project_categories')->nullOnDelete();
            
            // Sorting and status
            $table->integer('sort_order')->default(0)->after('parent_id');
            $table->boolean('is_active')->default(true)->after('sort_order');
            
            // SEO
            $table->string('meta_title')->nullable()->after('is_active');
            $table->text('meta_description')->nullable()->after('meta_title');
            
            // Indexes
            $table->index(['parent_id', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('project_categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'is_active']);
            $table->dropIndex(['sort_order']);
            
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'description',
                'icon',
                'color',
                'parent_id',
                'sort_order',
                'is_active',
                'meta_title',
                'meta_description'
            ]);
        });
    }
};