<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('almanac_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almanac_setup_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('event_column', ['academic', 'meeting']);
            $table->string('category', 100)->nullable();
            $table->boolean('applies_to_all')->default(true);
            $table->boolean('is_no_classes')->default(false);
            $table->boolean('is_tentative')->default(false);
            $table->string('background_color', 7)->nullable();
            $table->string('text_color', 7)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['almanac_setup_id', 'start_date', 'event_column'], 'almanac_event_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almanac_events');
    }
};
