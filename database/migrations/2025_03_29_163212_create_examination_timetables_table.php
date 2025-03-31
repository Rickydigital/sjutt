<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('examination_timetables', function (Blueprint $table) {
            $table->id();
            $table->string('timetable_type'); 
            $table->string('program'); 
            $table->string('semester'); 
            $table->string('course_code');
            $table->string('faculty');
            $table->integer('year');
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('examination_timetables');
    }
};

