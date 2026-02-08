<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_vice_candidates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_candidate_id')
                ->constrained('election_candidates')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained('faculties')
                ->nullOnDelete();

            $table->foreignId('program_id')
                ->nullable()
                ->constrained('programs')
                ->nullOnDelete();

            $table->string('photo')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();

            // One vice per candidate
            $table->unique('election_candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_vice_candidates');
    }
};
