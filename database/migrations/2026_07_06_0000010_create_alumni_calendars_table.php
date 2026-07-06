<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->date('calendar_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('venue')->nullable();
            $table->enum('type', ['meeting', 'assembly', 'convocation', 'deadline', 'event', 'other'])->default('event');
            $table->enum('status', ['draft', 'published', 'cancelled'])->default('published');
            $table->boolean('is_public')->default(true);
            $table->foreignId('alumni_event_id')->nullable()->constrained('alumni_events')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_calendars');
    }
};
