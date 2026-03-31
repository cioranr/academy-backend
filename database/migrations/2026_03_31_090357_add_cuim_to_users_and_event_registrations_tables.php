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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cuim', 20)->nullable()->after('professional_grade');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('cuim', 20)->nullable()->after('professional_grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('cuim');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cuim');
        });
    }
};
