<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_result_scopes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('result_publish_id')
                ->constrained('election_result_publishes')
                ->cascadeOnDelete();

            $table->enum('scope_type', ['global','faculty','program']);

            // For faculty/program scope
            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('program_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedInteger('eligible_students')->default(0);
            $table->unsignedInteger('voters')->default(0);
            $table->decimal('turnout_percent', 5, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_result_scopes');
    }
};
