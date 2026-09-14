<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('checkin_token', 64)->nullable()->unique()->after('questionnaire_id');
            $table->timestamp('checkin_token_generated_at')->nullable()->after('checkin_token');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('presence_method', 10)->nullable()->after('present_at'); // 'manual' | 'qr'
            $table->foreignId('presence_confirmed_by')->nullable()->after('presence_method')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('presence_confirmed_by');
            $table->dropColumn('presence_method');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['checkin_token']);
            $table->dropColumn(['checkin_token', 'checkin_token_generated_at']);
        });
    }
};
