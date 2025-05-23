<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyCalendarEventsTable extends Migration
{
    public function up()
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->unsignedInteger('week_number')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->unsignedInteger('week_number')->nullable(false)->change();
        });
    }
}