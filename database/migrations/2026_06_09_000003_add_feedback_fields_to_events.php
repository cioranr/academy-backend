<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('send_feedback')->default(false)->after('cmr_address');
            $table->foreignId('questionnaire_id')->nullable()->constrained('questionnaires')->nullOnDelete()->after('send_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['questionnaire_id']);
            $table->dropColumn(['send_feedback', 'questionnaire_id']);
        });
    }
};
