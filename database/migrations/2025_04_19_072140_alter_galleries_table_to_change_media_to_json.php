<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterGalleriesTableToChangeMediaToJson extends Migration
{
    public function up()
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->json('media')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->string('media')->nullable()->change();
        });
    }
}