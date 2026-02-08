<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropColumn(['open_at', 'close_at']);
            $table->time('open_time')->after('end_date');
            $table->time('close_time')->after('open_time');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time', function (Blueprint $table) {
            //
        });
    }
};
