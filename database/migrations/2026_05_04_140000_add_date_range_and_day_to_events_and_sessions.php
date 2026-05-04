<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('date');
        });

        Schema::table('event_sessions', function (Blueprint $table) {
            $table->unsignedInteger('day_index')->default(0)->after('event_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });

        Schema::table('event_sessions', function (Blueprint $table) {
            $table->dropColumn('day_index');
        });
    }
};
