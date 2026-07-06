<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni_election_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_election_id')->constrained('alumni_elections')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('max_candidates')->nullable();
            $table->unsignedInteger('max_votes_per_alumni')->default(1);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['alumni_election_id','name']);
        });
    }
    public function down(): void { Schema::dropIfExists('alumni_election_positions'); }
};
