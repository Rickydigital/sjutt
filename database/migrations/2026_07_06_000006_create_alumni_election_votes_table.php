<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni_election_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_election_id')->constrained('alumni_elections')->cascadeOnDelete();
            $table->foreignId('alumni_election_position_id')->constrained('alumni_election_positions')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('alumni_election_candidates')->cascadeOnDelete();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->string('vote_hmac', 128);
            $table->timestamp('voted_at');
            $table->timestamps();
            $table->unique(['alumni_election_id','alumni_election_position_id','alumni_id'], 'one_alumni_vote_per_position');
        });
    }
    public function down(): void { Schema::dropIfExists('alumni_election_votes'); }
};
