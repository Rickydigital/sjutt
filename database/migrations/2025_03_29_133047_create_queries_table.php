<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('queries', function (Blueprint $table) {
            $table->id();
            $table->text('description'); // Query description from the user
            $table->date('date_sent'); // Date the query was sent
            $table->enum('status', ['Received', 'Investigation', 'Processed'])->default('Received'); // Status of the query
            $table->string('track_number')->unique(); // Track number (auto-generated)
            $table->foreignId('admin_id')->nullable()->constrained('users'); // Admin who added progress (nullable until an admin fills it)
            $table->timestamps();
        });
    
        Schema::create('query_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('query_id')->constrained('queries'); // Foreign key to queries table
            $table->text('admin_description'); // Progress description by the admin
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queries');
        Schema::dropIfExists('query_progresses');
    }
};
