<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('form4_index')->nullable()->after('reg_no')->index();
            $table->string('nationality')->nullable()->after('gender');
            $table->string('disability')->nullable()->after('nationality');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['form4_index']);
            $table->dropColumn([
                'middle_name',
                'form4_index',
                'nationality',
                'disability',
            ]);
        });
    }
};