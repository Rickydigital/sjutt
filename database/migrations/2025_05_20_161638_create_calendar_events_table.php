<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarEventsTable extends Migration
{
    public function up()
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_setup_id')->constrained()->onDelete('cascade');
            $table->date('event_date');
            $table->string('category');
            $table->string('event_description');
            $table->integer('week_number')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('calendar_events');
    }
}