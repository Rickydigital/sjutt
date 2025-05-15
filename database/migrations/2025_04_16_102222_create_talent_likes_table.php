<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTalentLikesTable extends Migration
{
    public function up()
    {
        Schema::create('talent_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_content_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('talent_likes');
    }
}