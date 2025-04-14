<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuggestionsTable extends Migration
{
    public function up()
    {
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('sender_type', ['student', 'admin'])->nullable();
            $table->text('message');
            $table->boolean('is_anonymous')->default(false);
            $table->enum('status', ['Received', 'Viewed', 'Processed'])->default('Received');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('suggestions');
    }
}