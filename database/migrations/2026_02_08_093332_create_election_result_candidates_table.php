<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_result_candidates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('result_position_id')
                ->constrained('election_result_positions')
                ->cascadeOnDelete();

            $table->foreignId('election_candidate_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('candidate_name');
            $table->string('candidate_reg_no')->nullable();

            $table->unsignedInteger('vote_count')->default(0);
            $table->decimal('vote_percent', 5, 2)->default(0);

            $table->unsignedInteger('rank')->default(0);

            $table->boolean('is_winner')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_result_candidates');
    }
};

