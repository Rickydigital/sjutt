<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('reg_no')->unique(); // Registration number
            $table->integer('year_of_study')->unsigned(); // 1, 2, 3, or 4
            $table->string('faculty');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('gender', ['male', 'female']);
            $table->timestamps();
            $table->string('remember_token')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
}