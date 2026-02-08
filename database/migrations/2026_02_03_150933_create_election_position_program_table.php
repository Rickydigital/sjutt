<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_position_program', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_position_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('program_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['election_position_id', 'program_id'],
                'unique_position_program'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_position_program');
    }
};
