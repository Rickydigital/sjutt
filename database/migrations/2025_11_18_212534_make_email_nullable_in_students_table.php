<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Step 1: Drop the old unique index
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['email']);                    // removes students_email_unique
        });

        // Step 2: Change column to nullable + add NEW unique index (allows nulls)
        Schema::table('students', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            // Remove the unique constraint first
            $table->dropUnique(['email']);

            // Make email not nullable again + unique
            $table->string('email')->unique()->nullable(false)->change();
        });
    }
};