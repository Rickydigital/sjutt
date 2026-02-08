<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_general_election_officers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('student_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // optional metadata
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // prevent duplicates
            $table->unique(['election_id', 'student_id'], 'unique_election_officer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_general_election_officers');
    }
};
