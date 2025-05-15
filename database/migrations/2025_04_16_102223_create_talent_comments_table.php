<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTalentCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('talent_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_content_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('comment', 500);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('talent_comments');
    }
}