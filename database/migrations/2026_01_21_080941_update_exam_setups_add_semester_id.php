<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Step 1: Add semester_id only if it doesn't exist
        if (!Schema::hasColumn('exam_setups', 'semester_id')) {
            Schema::table('exam_setups', function (Blueprint $table) {
                $table->unsignedBigInteger('semester_id')->nullable()->after('id');
            });
        }

        // Step 2: Map existing semester values to semester_id
        $examSetups = DB::table('exam_setups')->whereNull('semester_id')->get();
        
        foreach ($examSetups as $examSetup) {
            $semesterId = null;
            
            // If semester column exists and has a value
            if (Schema::hasColumn('exam_setups', 'semester') && !empty($examSetup->semester)) {
                $semester = DB::table('semesters')
                    ->where('name', $examSetup->semester)
                    ->first();
                
                if ($semester) {
                    $semesterId = $semester->id;
                } else {
                    // Create new semester if it doesn't exist
                    $semesterId = DB::table('semesters')->insertGetId([
                        'name' => $examSetup->semester,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // Get or create default semester
                $defaultSemester = DB::table('semesters')->first();
                
                if (!$defaultSemester) {
                    $semesterId = DB::table('semesters')->insertGetId([
                        'name' => 'Semester 1',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $semesterId = $defaultSemester->id;
                }
            }
            
            // Update the exam_setup with semester_id
            DB::table('exam_setups')
                ->where('id', $examSetup->id)
                ->update(['semester_id' => $semesterId]);
        }

        // Step 3: Ensure all records have semester_id before adding constraint
        $nullCount = DB::table('exam_setups')->whereNull('semester_id')->count();
        
        if ($nullCount > 0) {
            $defaultSemester = DB::table('semesters')->first();
            if ($defaultSemester) {
                DB::table('exam_setups')
                    ->whereNull('semester_id')
                    ->update(['semester_id' => $defaultSemester->id]);
            }
        }

        // Step 4: Add foreign key constraint if it doesn't exist
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'exam_setups' 
            AND COLUMN_NAME = 'semester_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (empty($foreignKeys)) {
            Schema::table('exam_setups', function (Blueprint $table) {
                $table->unsignedBigInteger('semester_id')->nullable(false)->change();
                $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
            });
        }

        // Step 5: Drop the old semester column if it exists
        if (Schema::hasColumn('exam_setups', 'semester')) {
            Schema::table('exam_setups', function (Blueprint $table) {
                $table->dropColumn('semester');
            });
        }
    }

    public function down()
    {
        // Add back the semester column
        if (!Schema::hasColumn('exam_setups', 'semester')) {
            Schema::table('exam_setups', function (Blueprint $table) {
                $table->string('semester')->nullable()->after('semester_id');
            });
        }

        // Populate semester column from semester_id
        DB::table('exam_setups')->orderBy('id')->chunk(100, function ($setups) {
            foreach ($setups as $setup) {
                $semester = DB::table('semesters')->find($setup->semester_id);
                if ($semester) {
                    DB::table('exam_setups')
                        ->where('id', $setup->id)
                        ->update(['semester' => $semester->name]);
                }
            }
        });

        // Drop foreign key if exists
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'exam_setups' 
            AND COLUMN_NAME = 'semester_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (!empty($foreignKeys)) {
            Schema::table('exam_setups', function (Blueprint $table) {
                $table->dropForeign(['semester_id']);
            });
        }

        // Drop semester_id column
        if (Schema::hasColumn('exam_setups', 'semester_id')) {
            Schema::table('exam_setups', function (Blueprint $table) {
                $table->dropColumn('semester_id');
            });
        }
    }
};