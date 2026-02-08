<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_result_positions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('result_scope_id')
                ->constrained('election_result_scopes')
                ->cascadeOnDelete();

            $table->foreignId('election_position_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('position_name');

            $table->unsignedInteger('eligible_students')->default(0);
            $table->unsignedInteger('voters')->default(0);
            $table->decimal('turnout_percent', 5, 2)->default(0);

            $table->foreignId('winner_candidate_id')
                ->nullable()
                ->constrained('election_candidates')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_result_positions');
    }
};

