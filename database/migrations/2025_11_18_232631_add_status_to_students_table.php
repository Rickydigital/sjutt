<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->enum('status', ['Deactive', 'Active', 'Alumni'])
                  ->default('Deactive')
                  ->after('can_upload');
        });

        // THIS WILL UPDATE ALL EXISTING STUDENTS TO Deactive
        DB::table('students')->update(['status' => 'Deactive']);
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};