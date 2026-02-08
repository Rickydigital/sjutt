<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_result_candidate_breakdowns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('result_candidate_id')
                ->constrained('election_result_candidates')
                ->cascadeOnDelete();

            $table->enum('scope_type', ['faculty','program']);

            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('program_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedInteger('vote_count')->default(0);
            $table->decimal('vote_percent', 5, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_result_candidate_breakdowns');
    }
};

