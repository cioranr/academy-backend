<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('specialty')->nullable()->after('phone');
            $table->string('professional_grade')->nullable()->after('specialty');
            $table->enum('role', ['participant', 'doctor', 'admin', 'events_manager'])->default('participant')->after('professional_grade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone', 'specialty', 'professional_grade', 'role']);
        });
    }
};
