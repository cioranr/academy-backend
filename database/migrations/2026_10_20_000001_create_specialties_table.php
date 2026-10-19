<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('value', 100)->unique();
            $table->string('label');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $rows = [
            ['cardiologie', 'Cardiologie'],
            ['chirurgie', 'Chirurgie cardiovasculară'],
            ['chirurgie-generala', 'Chirurgie generală'],
            ['chirurgie-plastica', 'Chirurgie plastică'],
            ['oncologie', 'Oncologie'],
            ['radiologie', 'Radiologie'],
            ['radioterapie', 'Radioterapie'],
            ['rezidenti', 'Rezidenți'],
            ['alta', 'Altă specialitate'],
        ];

        DB::table('specialties')->insert(array_map(fn ($r, $i) => [
            'value' => $r[0], 'label' => $r[1], 'order' => ($i + 1) * 10,
            'created_at' => $now, 'updated_at' => $now,
        ], $rows, array_keys($rows)));
    }

    public function down(): void
    {
        Schema::dropIfExists('specialties');
    }
};
