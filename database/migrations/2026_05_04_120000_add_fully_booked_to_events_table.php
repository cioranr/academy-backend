<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->text('fully_booked_message')->nullable()->after('max_participants');
            $table->boolean('show_fully_booked_message')->default(false)->after('fully_booked_message');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['fully_booked_message', 'show_fully_booked_message']);
        });
    }
};
