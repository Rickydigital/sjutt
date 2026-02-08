<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_candidates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_position_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('student_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('faculty_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('program_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->boolean('is_approved')->default(false);

            $table->timestamps();

            $table->unique(
                ['election_position_id', 'student_id'],
                'unique_candidate_per_position'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_candidates');
    }
};
