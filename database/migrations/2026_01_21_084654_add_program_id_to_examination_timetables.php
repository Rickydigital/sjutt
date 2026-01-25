<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('examination_timetables', function (Blueprint $table) {
            // Add program_id as nullable first
            $table->unsignedBigInteger('program_id')->nullable()->after('id');
        });

        // Update existing records with program_id from their faculty
        DB::table('examination_timetables')
            ->whereNull('program_id')
            ->orderBy('id')
            ->chunk(100, function ($exams) {
                foreach ($exams as $exam) {
                    $faculty = DB::table('faculties')->find($exam->faculty_id);
                    if ($faculty && $faculty->program_id) {
                        DB::table('examination_timetables')
                            ->where('id', $exam->id)
                            ->update(['program_id' => $faculty->program_id]);
                    }
                }
            });

        // Make program_id NOT NULL and add foreign key
        Schema::table('examination_timetables', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable(false)->change();
            $table->foreign('program_id', 'exam_tt_program_id_fk')
                  ->references('id')
                  ->on('programs')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('examination_timetables', function (Blueprint $table) {
            $table->dropForeign('exam_tt_program_id_fk');
            $table->dropColumn('program_id');
        });
    }
};