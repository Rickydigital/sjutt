<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('examination_timetables', function (Blueprint $table) {
            // Add exam_setup_id
            $table->foreignId('exam_setup_id')->after('end_time')->constrained('exam_setups')->onDelete('cascade');
            
            // Remove group_selection
            if (Schema::hasColumn('examination_timetables', 'group_selection')) {
                $table->dropColumn('group_selection');
            }
            
            // Make venue_id nullable temporarily (we'll remove it after migrating data)
            if (Schema::hasColumn('examination_timetables', 'venue_id')) {
                // Drop foreign key first if it exists
                try {
                    $table->dropForeign(['venue_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->unsignedBigInteger('venue_id')->nullable()->change();
            }
        });
    }

    public function down()
    {
        Schema::table('examination_timetables', function (Blueprint $table) {
            // Drop exam_setup_id
            $table->dropForeign(['exam_setup_id']);
            $table->dropColumn('exam_setup_id');
            
            // Restore group_selection
            $table->string('group_selection')->nullable();
            
            // Restore venue_id foreign key
            if (Schema::hasColumn('examination_timetables', 'venue_id')) {
                $table->foreign('venue_id')->references('id')->on('venues')->onDelete('set null');
            }
        });
    }
};