<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            // Drop old column if needed, or just alter
            $table->json('user_allowed')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('user_allowed')->nullable()->change();
        });
    }
};