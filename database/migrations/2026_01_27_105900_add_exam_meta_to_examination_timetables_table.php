<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examination_timetables', function (Blueprint $table) {
            // Add new columns
            $table->date('marking_date')->nullable()->after('exam_date');
            $table->date('uploading_date')->nullable()->after('marking_date');

            // You can switch to enum later if you want
            $table->string('nature', 20)->default('Theory')->after('uploading_date');
            // OR:
            // $table->enum('nature', ['Theory', 'Practical'])->default('Theory')->after('uploading_date');
        });

        /**
         * IMPORTANT:
         * Backfill existing records so old rows don't remain NULL
         */
        DB::table('examination_timetables')
            ->whereNull('nature')
            ->update([
                'nature' => 'Theory'
            ]);
    }

    public function down(): void
    {
        Schema::table('examination_timetables', function (Blueprint $table) {
            $table->dropColumn([
                'marking_date',
                'uploading_date',
                'nature',
            ]);
        });
    }
};