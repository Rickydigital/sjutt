<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni_election_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_election_id')->constrained('alumni_elections')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['officer','supervisor','verifier'])->default('officer');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['alumni_election_id','user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('alumni_election_officers'); }
};
