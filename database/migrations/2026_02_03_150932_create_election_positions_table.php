<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_positions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('position_definition_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->enum('scope_type', ['faculty', 'program', 'global']);

            $table->boolean('is_enabled')->default(true);

            $table->timestamps();

            $table->unique(
                ['election_id', 'position_definition_id'],
                'unique_position_per_election'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_positions');
    }
};
