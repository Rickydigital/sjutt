<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    

public function up()
{
    Schema::table('galleries', function (Blueprint $table) {
        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('galleries', function (Blueprint $table) {
        $table->dropForeign(['created_by']);
        $table->dropColumn('created_by');
    });
}
};
