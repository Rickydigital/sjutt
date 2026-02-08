<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_votes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('election_position_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('candidate_id')
                  ->constrained('election_candidates')
                  ->cascadeOnDelete();

            $table->foreignId('student_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->timestamps();

            // 🔐 Prevent double voting
            $table->unique(
                ['election_id', 'election_position_id', 'student_id'],
                'unique_vote_per_position'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_votes');
    }
};
