<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('almanac_week_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almanac_setup_id')->constrained()->cascadeOnDelete();
            $table->foreignId('almanac_program_group_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('display_value', 30)->nullable();
            $table->enum('block_type', [
                'teaching', 'examination', 'registration', 'orientation',
                'fieldwork', 'clinical', 'holiday', 'break', 'other',
            ])->default('teaching');
            $table->string('background_color', 7)->nullable();
            $table->string('text_color', 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['almanac_program_group_id', 'start_date', 'end_date'], 'almanac_week_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almanac_week_blocks');
    }
};
