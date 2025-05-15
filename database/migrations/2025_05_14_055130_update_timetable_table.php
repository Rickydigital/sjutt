<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('timetables', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn([ 'time_start', 'time_end']);
            // Add new columns
            $table->foreignId('lecturer_id')->nullable()->after('venue_id')->constrained('users')->onDelete('set null');
            $table->string('group_selection')->after('lecturer_id');
            $table->time('time_slot')->after('day');
        });
    }

    public function down()
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->string('faculty')->after('id');
            $table->string('year')->after('faculty');
            $table->string('venue')->after('activity');
            $table->time('time_start')->after('day');
            $table->time('time_end')->after('time_start');
            $table->dropColumn(['faculty_id', 'year_id', 'venue_id', 'lecturer_id', 'group_selection', 'time_slot']);
        });
    }
};