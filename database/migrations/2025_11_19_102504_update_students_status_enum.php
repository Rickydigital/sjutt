<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->enum('status', ['Inactive', 'Active', 'Alumni'])
                  ->default('Inactive') // Update the default to the new value
                  ->change();
        });

        // Optional: Update any existing 'Deactive' records to 'Inactive'
        // This is crucial to prevent errors if the old value is still present.
        DB::table('students')
          ->where('status', 'Deactive')
          ->update(['status' => 'Inactive']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the enum change and update the default back
        Schema::table('students', function (Blueprint $table) {
            $table->enum('status', ['Deactive', 'Active', 'Alumni'])
                  ->default('Deactive')
                  ->change();
        });

        // Optional: Reverse the data update, changing 'Inactive' back to 'Deactive'
        DB::table('students')
          ->where('status', 'Inactive')
          ->update(['status' => 'Deactive']);
    }
};