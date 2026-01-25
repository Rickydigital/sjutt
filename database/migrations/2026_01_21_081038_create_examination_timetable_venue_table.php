<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('examination_timetable_venue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_timetable_id')->constrained('examination_timetables')->onDelete('cascade');
            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
            $table->integer('allocated_capacity')->nullable()->comment('Number of students allocated to this venue');
            $table->timestamps();

            // Unique constraint to prevent duplicate venue assignments
            $table->unique(['examination_timetable_id', 'venue_id'], 'exam_venue_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('examination_timetable_venue');
    }
};