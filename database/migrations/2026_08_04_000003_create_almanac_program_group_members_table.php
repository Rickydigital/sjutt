<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('almanac_program_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almanac_program_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['almanac_program_group_id', 'program_id'], 'almanac_group_program_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almanac_program_group_members');
    }
};
