<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('cmr_address')->nullable()->after('schema_org');
        });

        Schema::table('event_speakers', function (Blueprint $table) {
            $table->string('signature')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('cmr_address');
        });

        Schema::table('event_speakers', function (Blueprint $table) {
            $table->dropColumn('signature');
        });
    }
};
