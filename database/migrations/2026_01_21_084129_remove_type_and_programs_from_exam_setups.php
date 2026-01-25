<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('exam_setups', function (Blueprint $table) {
            // Drop type column if it exists
            if (Schema::hasColumn('exam_setups', 'type')) {
                $table->dropColumn('type');
            }
            
            // Drop programs column if it exists
            if (Schema::hasColumn('exam_setups', 'programs')) {
                $table->dropColumn('programs');
            }
        });
    }

    public function down()
    {
        Schema::table('exam_setups', function (Blueprint $table) {
            // Restore type column
            $table->json('type')->nullable()->after('semester_id');
            
            // Restore programs column
            $table->json('programs')->nullable()->after('time_slots');
        });
    }
};