<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_simplify_suggestions_table_for_full_anonymity.php
public function up()
{
    Schema::table('suggestions', function (Blueprint $table) {
        $table->dropForeign(['student_id']);
        $table->dropForeign(['user_id']);
        $table->dropColumn(['student_id', 'user_id', 'deleted_for']);
        
    });
}

public function down()
{
    Schema::table('suggestions', function (Blueprint $table) {
        $table->unsignedBigInteger('student_id')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('deleted_for')->nullable();
        
        $table->foreign('student_id')->references('id')->on('students');
        $table->foreign('user_id')->references('id')->on('users');
    });
}
};
