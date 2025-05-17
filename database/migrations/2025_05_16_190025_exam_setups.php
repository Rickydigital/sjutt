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
        Schema::create('exam_setups', function (Blueprint $table) {
            $table->id();
            $table->json('type'); // Changed to JSON to store multiple types
            $table->string('academic_year'); // e.g., "2024/2025"
            $table->string('semester'); // e.g., "1", "2", "Final"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('include_weekends')->default(false);
            $table->json('time_slots');
            $table->json('programs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_setups');
    }
};
