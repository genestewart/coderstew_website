<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Store optimized image variants as JSON
            $table->json('featured_image_variants')->nullable()->after('featured_image');
            $table->json('gallery_image_variants')->nullable()->after('gallery_images');
            
            // Store image metadata for optimization tracking
            $table->json('image_metadata')->nullable()->after('gallery_image_variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['featured_image_variants', 'gallery_image_variants', 'image_metadata']);
        });
    }
};