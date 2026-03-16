<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('credits_label');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->longText('schema_org')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'schema_org']);
        });
    }
};
