<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarSetupsTable extends Migration
{
    public function up()
    {
        Schema::create('calendar_setups', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('calendar_setups');
    }
}