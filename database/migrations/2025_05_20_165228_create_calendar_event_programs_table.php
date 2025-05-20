<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarEventProgramsTable extends Migration
{
    public function up()
    {
        Schema::create('calendar_event_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained('calendar_events')->onDelete('cascade');
            $table->string('program')->index(); // e.g., 'Degree Health'
            $table->unsignedInteger('custom_week_number');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('calendar_event_programs');
    }
}