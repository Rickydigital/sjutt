<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polling_centres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')
                ->constrained('elections')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('location')->nullable();

            $table->string('manager_name')->nullable();
            $table->string('manager_phone')->nullable();
            $table->string('manager_email')->nullable();

            $table->string('public_token_hash', 128)->unique();
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('successful_sessions')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_centres');
    }
};