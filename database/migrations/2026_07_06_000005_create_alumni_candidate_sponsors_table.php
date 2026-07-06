<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni_candidate_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('alumni_election_candidates')->cascadeOnDelete();
            $table->string('name');
            $table->string('faculty_school_directorate')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('alumni_candidate_sponsors'); }
};
