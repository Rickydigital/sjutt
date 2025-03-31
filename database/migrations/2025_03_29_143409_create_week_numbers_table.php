<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeekNumbersTable extends Migration
{
    public function up()
    {
        Schema::create('week_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calendar_id'); // Foreign key to calendars
            $table->integer('week_number'); // e.g., 23, 25, 78, 45
            $table->string('program_category'); // e.g., "Degree Non-health", "Degree Health"
            $table->timestamps();

            $table->foreign('calendar_id')->references('id')->on('calendars')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('week_numbers');
    }
}
