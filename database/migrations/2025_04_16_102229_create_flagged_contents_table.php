<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlaggedContentsTable extends Migration
{
    public function up()
    {
        Schema::create('flagged_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_content_id')->constrained()->onDelete('cascade');
            $table->text('reason');
            $table->string('flagged_by'); // e.g., 'system', 'admin'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('flagged_contents');
    }
}