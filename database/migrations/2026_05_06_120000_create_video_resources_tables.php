<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('video_path')->nullable();   // /storage/video-resources/...
            $table->string('video_embed')->nullable();  // YouTube / Vimeo URL
            $table->boolean('active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('active');
        });

        Schema::create('video_resource_doctor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['video_resource_id', 'doctor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_resource_doctor');
        Schema::dropIfExists('video_resources');
    }
};
