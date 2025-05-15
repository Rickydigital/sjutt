<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('timetables', function (Blueprint $table) {
            
            $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade');
            $table->foreignId('year_id')->constrained('years')->onDelete('cascade');
          $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
            $table->dropForeign(['year_id']);
            $table->dropForeign(['venue_id']);

          
        });
    }
};

