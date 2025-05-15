<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyFacultiesTable extends Migration
{
    public function up()
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->integer('total_students_no')->default(0)->after('name');
            $table->text('description')->nullable()->after('total_students_no');
            $table->unsignedBigInteger('program_id')->nullable()->after('description');
            
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropColumn(['total_students_no', 'description', 'program_id']);
        });
    }
}