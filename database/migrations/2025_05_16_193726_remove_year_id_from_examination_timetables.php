<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveYearIdFromExaminationTimetables extends Migration
{
    public function up()
    {
        Schema::table('examination_timetables', function (Blueprint $table) {
            $table->dropForeign(['year_id']); // Drop foreign key if it exists
            $table->dropColumn('year_id');    // Drop the column
        });
    }

    public function down()
    {
        Schema::table('examination_timetables', function (Blueprint $table) {
            $table->unsignedBigInteger('year_id')->after('faculty_id');
            $table->foreign('year_id')->references('id')->on('years')->onDelete('cascade');
        });
    }
}