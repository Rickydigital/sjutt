<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('election_result_publishes', function (Blueprint $table) {
            // drop old FK to users
            $table->dropForeign(['published_by']);

            // if published_by is not unsignedBigInteger, fix it:
            $table->unsignedBigInteger('published_by')->change();

            // new FK to students
            $table->foreign('published_by')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('election_result_publishes', function (Blueprint $table) {
            $table->dropForeign(['published_by']);

            $table->foreign('published_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
