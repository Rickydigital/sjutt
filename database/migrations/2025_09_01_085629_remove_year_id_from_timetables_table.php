<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('timetables', function (Blueprint $table) {
            // Drop foreign key first, then the column
            $table->dropForeign(['year_id']);
            $table->dropColumn('year_id');
        });
    }

    public function down() {
        Schema::table('timetables', function (Blueprint $table) {
            // Re-add year_id column and foreign key in case of rollback
            $table->foreignId('year_id')->constrained('years')->onDelete('cascade');
        });
    }
};
