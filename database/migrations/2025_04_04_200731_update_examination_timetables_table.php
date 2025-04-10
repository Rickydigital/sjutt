<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('examination_timetables', function (Blueprint $table) {
            // $table->dropColumn('faculty');
            // $table->dropColumn('year');
            // $table->dropColumn('venue');

            // $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade');
            // $table->foreignId('year_id')->constrained('years')->onDelete('cascade');
            // $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::table('examination_timetables', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
            $table->dropForeign(['year_id']);
            $table->dropForeign(['venue_id']);

            $table->string('faculty');
            $table->integer('year');
            $table->string('venue');
        });
    }
};
