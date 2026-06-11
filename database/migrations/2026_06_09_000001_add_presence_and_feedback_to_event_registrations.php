<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->boolean('is_present')->default(false)->after('status');
            $table->timestamp('present_at')->nullable()->after('is_present');
            $table->timestamp('feedback_sent_at')->nullable()->after('present_at');
            $table->string('feedback_token')->nullable()->unique()->after('feedback_sent_at');
            $table->boolean('feedback_completed')->default(false)->after('feedback_token');
            $table->boolean('diploma_sent')->default(false)->after('feedback_completed');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['is_present', 'present_at', 'feedback_sent_at', 'feedback_token', 'feedback_completed', 'diploma_sent']);
        });
    }
};
