<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('signature')->nullable()->after('image');
        });

        // Best-effort migration of any existing speaker signatures up to the doctor row.
        if (Schema::hasColumn('event_speakers', 'signature')) {
            DB::table('event_speakers')
                ->whereNotNull('signature')
                ->whereNotNull('doctor_id')
                ->orderBy('id')
                ->get(['doctor_id', 'signature'])
                ->each(function ($row) {
                    DB::table('doctors')
                        ->where('id', $row->doctor_id)
                        ->whereNull('signature')
                        ->update(['signature' => $row->signature]);
                });

            Schema::table('event_speakers', function (Blueprint $table) {
                $table->dropColumn('signature');
            });
        }
    }

    public function down(): void
    {
        Schema::table('event_speakers', function (Blueprint $table) {
            $table->string('signature')->nullable()->after('image');
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn('signature');
        });
    }
};
