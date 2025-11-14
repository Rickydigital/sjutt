<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_add_times_to_events_table.php
public function up()
{
    Schema::table('events', function (Blueprint $table) {
        $table->dateTime('start_time')->after('event_time')->nullable();
        $table->dateTime('end_time')->after('start_time')->nullable();
        $table->dropColumn('event_time'); // remove old column
    });
}

public function down()
{
    Schema::table('events', function (Blueprint $table) {
        $table->dateTime('event_time')->after('location');
        $table->dropColumn(['start_time', 'end_time']);
    });
}
};
