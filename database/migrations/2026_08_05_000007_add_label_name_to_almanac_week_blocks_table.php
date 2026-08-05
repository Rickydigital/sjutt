<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('almanac_week_blocks', function (Blueprint $table) {
            $table->string('label_name', 50)->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('almanac_week_blocks', function (Blueprint $table) {
            $table->dropColumn('label_name');
        });
    }
};
