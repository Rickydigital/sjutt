<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->time('time_start')->after('day');
            $table->time('time_end')->after('time_start');
            $table->dropColumn('time_slot');
        });
    }

    public function down()
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->time('time_slot')->after('day');
            $table->dropColumn(['time_start', 'time_end']);
        });
    }
};