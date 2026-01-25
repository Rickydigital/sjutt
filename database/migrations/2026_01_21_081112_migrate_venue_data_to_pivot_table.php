<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Only migrate data if examination_timetable_venue table exists
        // and examination_timetables has venue_id column
        if (Schema::hasTable('examination_timetable_venue') && 
            Schema::hasColumn('examination_timetables', 'venue_id')) {
            
            // Migrate existing venue_id to pivot table
            DB::table('examination_timetables')
                ->whereNotNull('venue_id')
                ->orderBy('id')
                ->chunk(100, function ($examinations) {
                    foreach ($examinations as $exam) {
                        // Check if pivot entry doesn't already exist
                        $exists = DB::table('examination_timetable_venue')
                            ->where('examination_timetable_id', $exam->id)
                            ->where('venue_id', $exam->venue_id)
                            ->exists();

                        if (!$exists) {
                            DB::table('examination_timetable_venue')->insert([
                                'examination_timetable_id' => $exam->id,
                                'venue_id' => $exam->venue_id,
                                'allocated_capacity' => null,
                                'created_at' => $exam->created_at ?? now(),
                                'updated_at' => $exam->updated_at ?? now(),
                            ]);
                        }
                    }
                });

            // Get the actual foreign key name
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'examination_timetables' 
                AND COLUMN_NAME = 'venue_id' 
                AND REFERENCED_TABLE_NAME = 'venues'
            ");

            // Drop foreign key if it exists
            if (!empty($foreignKeys)) {
                $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE examination_timetables DROP FOREIGN KEY `{$foreignKeyName}`");
            }

            // Now drop the venue_id column
            Schema::table('examination_timetables', function (Blueprint $table) {
                $table->dropColumn('venue_id');
            });
        }
    }

    public function down()
    {
        // Restore venue_id column
        if (!Schema::hasColumn('examination_timetables', 'venue_id')) {
            Schema::table('examination_timetables', function (Blueprint $table) {
                $table->unsignedBigInteger('venue_id')->nullable()->after('end_time');
            });

            // Migrate first venue back from pivot table to examination_timetables
            DB::table('examination_timetable_venue')
                ->select('examination_timetable_id', DB::raw('MIN(venue_id) as venue_id'))
                ->groupBy('examination_timetable_id')
                ->orderBy('examination_timetable_id')
                ->chunk(100, function ($pivots) {
                    foreach ($pivots as $pivot) {
                        DB::table('examination_timetables')
                            ->where('id', $pivot->examination_timetable_id)
                            ->update(['venue_id' => $pivot->venue_id]);
                    }
                });

            // Add foreign key back
            Schema::table('examination_timetables', function (Blueprint $table) {
                $table->foreign('venue_id', 'exam_tt_venue_id_fk')
                      ->references('id')
                      ->on('venues')
                      ->onDelete('set null');
            });
        }
    }
};