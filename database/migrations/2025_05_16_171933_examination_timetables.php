<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('examination_timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->onDelete('cascade');
            $table->foreignId('year_id')->constrained()->onDelete('cascade');
            $table->string('course_code');
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->string('group_selection');
            $table->timestamps();

            $table->foreign('course_code')->references('course_code')->on('courses')->onDelete('cascade');
        });

        // Pivot table for examination timetable lecturers
        Schema::create('examination_timetable_lecturer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_timetable_id')->constrained('examination_timetables')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examination_timetable_lecturer');
        Schema::dropIfExists('examination_timetables');
    }
};