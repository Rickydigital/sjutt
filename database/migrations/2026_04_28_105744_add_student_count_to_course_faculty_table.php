<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_faculty', function (Blueprint $table) {
            $table->unsignedInteger('student_count')->default(0)->after('faculty_id');

            $table->unique(['course_id', 'faculty_id']);
        });
    }

    public function down(): void
    {
        Schema::table('course_faculty', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'faculty_id']);
            $table->dropColumn('student_count');
        });
    }
};