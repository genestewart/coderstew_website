<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            // Technology details
            $table->text('description')->nullable()->after('slug');
            $table->string('icon')->nullable()->after('description'); // Icon/logo file path
            $table->string('color')->nullable()->after('icon'); // Brand color (hex)
            $table->string('website_url')->nullable()->after('color');
            
            // Categorization
            $table->enum('type', ['language', 'framework', 'library', 'tool', 'database', 'service', 'other'])
                  ->default('other')->after('website_url');
            
            // Sorting and status
            $table->integer('sort_order')->default(0)->after('type');
            $table->boolean('is_active')->default(true)->after('sort_order');
            
            // Indexes
            $table->index(['type', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_active']);
            $table->dropIndex(['sort_order']);
            
            $table->dropColumn([
                'description',
                'icon',
                'color',
                'website_url',
                'type',
                'sort_order',
                'is_active'
            ]);
        });
    }
};