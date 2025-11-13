<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCrossCateringAndWorkshopToCoursesTable extends Migration
{
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('cross_catering')->default(false)->after('session');
            $table->boolean('is_workshop')->default(false)->after('cross_catering');
        });
    }

    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['cross_catering', 'is_workshop']);
        });
    }
}