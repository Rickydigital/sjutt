<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('position_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // CR, FBR, PRES
            $table->string('name');

            $table->enum('default_scope_type', ['faculty', 'program', 'global']);

            $table->unsignedTinyInteger('max_votes_per_voter')->default(1);

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_definitions');
    }
};
