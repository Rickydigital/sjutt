<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });

        // The old foreign key made this column contain semesters.id. The
        // timetable code now scopes entries by timetable_semesters.id, so map
        // legacy rows to the newest matching setup before changing the FK.
        $legacySemesterIds = DB::table('timetables')
            ->whereNotNull('semester_id')
            ->distinct()
            ->pluck('semester_id');

        foreach ($legacySemesterIds as $semesterId) {
            // Some installations have already been writing setup IDs into this
            // column. They succeeded only when that number also happened to
            // exist in semesters.id. Preserve those rows in their existing
            // setup instead of moving them to a newer academic year.
            $alreadyASetupId = DB::table('timetable_semesters')
                ->where('id', $semesterId)
                ->exists();

            if ($alreadyASetupId) {
                continue;
            }

            $setupId = DB::table('timetable_semesters')
                ->where('semester_id', $semesterId)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderByDesc('activated_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('timetables')
                ->where('semester_id', $semesterId)
                ->update(['semester_id' => $setupId]);
        }

        Schema::table('timetables', function (Blueprint $table) {
            $table->foreign('semester_id')
                ->references('id')
                ->on('timetable_semesters')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });

        DB::table('timetables')
            ->whereNotNull('semester_id')
            ->orderBy('id')
            ->eachById(function ($row): void {
                $semesterId = DB::table('timetable_semesters')
                    ->where('id', $row->semester_id)
                    ->value('semester_id');

                DB::table('timetables')
                    ->where('id', $row->id)
                    ->update(['semester_id' => $semesterId]);
            });

        Schema::table('timetables', function (Blueprint $table) {
            $table->foreign('semester_id')
                ->references('id')
                ->on('semesters')
                ->nullOnDelete();
        });
    }
};
