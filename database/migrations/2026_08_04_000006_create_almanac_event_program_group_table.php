<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('almanac_event_program_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almanac_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('almanac_program_group_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['almanac_event_id', 'almanac_program_group_id'], 'almanac_event_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almanac_event_program_group');
    }
};
