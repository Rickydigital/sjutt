<?php

// database/migrations/2025_03_29_create_calendars_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarsTable extends Migration
{
    public function up()
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->string('month'); // e.g., December
            $table->string('dates'); // e.g., "Mon-25"
            $table->text('academic_calendar')->nullable(); // e.g., "Final day of 6-weeks field work practice"
            $table->text('meeting_activities_calendar')->nullable(); // e.g., "Students Services Committee"
            $table->year('academic_year'); // e.g., 2025
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('calendars');
    }
}
