<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_result_publishes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('published_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('published_at')->useCurrent();

            $table->string('version')->nullable(); // optional version label
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_result_publishes');
    }
};
