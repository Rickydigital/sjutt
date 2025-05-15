<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTalentContentsTable extends Migration
{
    public function up()
    {
        Schema::create('talent_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('content_type'); // e.g., 'video', 'audio', 'image'
            $table->string('file_path'); // Path to the uploaded file
            $table->text('description');
            $table->string('social_media_link')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'approved', 'flagged'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('talent_contents');
    }
}