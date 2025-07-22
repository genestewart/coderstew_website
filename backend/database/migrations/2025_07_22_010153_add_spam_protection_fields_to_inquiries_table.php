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
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('email');
            $table->ipAddress('ip_address')->nullable()->after('message');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->timestamp('submitted_at')->nullable()->after('user_agent');
            $table->enum('status', ['pending', 'reviewed', 'replied', 'spam'])->default('pending')->after('submitted_at');
            $table->unsignedTinyInteger('spam_score')->default(0)->after('status');
            $table->text('admin_notes')->nullable()->after('spam_score');
            
            // Add indexes for better performance
            $table->index('status');
            $table->index('spam_score');
            $table->index('submitted_at');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['inquiries_status_index']);
            $table->dropIndex(['inquiries_spam_score_index']);
            $table->dropIndex(['inquiries_submitted_at_index']);
            $table->dropIndex(['inquiries_status_created_at_index']);
            
            $table->dropColumn([
                'subject',
                'ip_address',
                'user_agent',
                'submitted_at',
                'status',
                'spam_score',
                'admin_notes'
            ]);
        });
    }
};
