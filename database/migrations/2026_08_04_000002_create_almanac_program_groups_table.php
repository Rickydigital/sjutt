<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('almanac_program_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almanac_setup_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('level', 100)->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->string('background_color', 7)->nullable();
            $table->string('text_color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['almanac_setup_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almanac_program_groups');
    }
};
