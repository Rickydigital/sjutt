<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Course;
use App\Models\Semester;

class AddSemesterIdToCoursesTable extends Migration
{
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->after('session');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('set null');
        });

        // Set all existing courses to First Semester (assuming ID 1 from seeder)
        $firstSemester = Semester::where('name', 'First Semester')->first();
        if ($firstSemester) {
            Course::query()->update(['semester_id' => $firstSemester->id]);
        }
    }

    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
    }
}