<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Step 1: Temporarily change to longtext (accepts anything)
        Schema::table('events', function (Blueprint $table) {
            $table->longText('location')->nullable()->change();
        });

        // Step 2: Convert all existing string values → JSON array ["Old Value"]
        DB::statement("
            UPDATE events 
            SET location = CONCAT('[\"', REPLACE(location, '\"', '\\\"'), '\"]') 
            WHERE location IS NOT NULL 
              AND location != '' 
              AND location NOT LIKE '[%'
        ");

        // Step 3: Now safely convert to json
        Schema::table('events', function (Blueprint $table) {
            $table->json('location')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location')->nullable()->change();
        });
    }
};