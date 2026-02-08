<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_position_faculty', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_position_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('faculty_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['election_position_id', 'faculty_id'],
                'unique_position_faculty'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_position_faculty');
    }
};
