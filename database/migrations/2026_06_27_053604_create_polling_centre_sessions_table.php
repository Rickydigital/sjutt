<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polling_centre_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('polling_centre_id')
                ->constrained('polling_centres')
                ->cascadeOnDelete();

            $table->foreignId('election_id')
                ->constrained('elections')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();

            $table->string('reg_no')->nullable();
            $table->string('form4_index')->nullable();
            $table->string('last_name')->nullable();

            $table->string('status')->default('started');
            // started, reg_verified, identity_verified, completed, failed, expired

            $table->unsignedInteger('votes_cast')->default(0);

            $table->string('session_token_hash', 128)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_centre_sessions');
    }
};