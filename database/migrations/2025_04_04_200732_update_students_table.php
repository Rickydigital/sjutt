<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('students', function (Blueprint $table) {
           // $table->dropColumn('faculty');
           // $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade');
            $table->string('name'); // New column for student name
        });
    }

    public function down() {
        Schema::table('students', function (Blueprint $table) {
            // $table->dropForeign(['faculty_id']);
            // $table->string('faculty');
            $table->dropColumn('name');
        });
    }
};
