<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyVenuesTable extends Migration
{
    public function up()
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->unsignedBigInteger('building_id')->nullable()->after('name');
            $table->integer('capacity')->after('building_id');
            $table->enum('type', [
                'lecture_theatre',
                'seminar_room',
                'computer_lab',
                'physics_lab',
                'chemistry_lab',
                'medical_lab',
                'nursing_demo',
                'pharmacy_lab',
                'other'
            ])->after('capacity');
            $table->string('longform')->after('type');
            
            $table->foreign('building_id')->references('id')->on('buildings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropForeign(['building_id']);
            $table->dropColumn(['building_id', 'capacity', 'type', 'longform']);
        });
    }
}