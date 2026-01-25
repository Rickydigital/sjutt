<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('examination_timetable_supervisor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('examination_timetable_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->string('supervisor_role')->default('Invigilator')->comment('Chief Invigilator, Invigilator, etc.');
            $table->timestamps();

            // Add foreign keys with custom short names
            $table->foreign('examination_timetable_id', 'exam_sup_exam_id_fk')
                  ->references('id')
                  ->on('examination_timetables')
                  ->onDelete('cascade');

            $table->foreign('user_id', 'exam_sup_user_id_fk')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('venue_id', 'exam_sup_venue_id_fk')
                  ->references('id')
                  ->on('venues')
                  ->onDelete('set null');

            // Index for faster queries
            $table->index(['examination_timetable_id', 'user_id'], 'exam_supervisor_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('examination_timetable_supervisor');
    }
};