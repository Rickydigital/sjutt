<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('faculty');
            $table->dropColumn('year_of_study');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->onDelete('cascade');
            $table->foreignId('program_id')->nullable()->constrained('programs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
        // First, drop the foreign key constraint
        $table->dropForeign(['faculty_id']);
        $table->dropForeign(['program_id']);

        // Then, drop the new columns
        $table->dropColumn('first_name');
        $table->dropColumn('last_name');
        $table->dropColumn('faculty_id');
        $table->dropColumn('program_id');

        // Finally, re-add the original columns
        $table->string('name');
        $table->string('faculty');
        $table->integer('year_of_study')->nullable();
        });
    }
};
