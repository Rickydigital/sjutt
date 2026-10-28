<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_lecturer', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('user_id')->index();
            $table->timestamp('assigned_at')->nullable()->after('is_active');
            $table->timestamp('removed_at')->nullable()->after('assigned_at');
        });

        DB::table('course_lecturer')->update([
            'is_active' => true,
            'assigned_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('course_lecturer', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'assigned_at', 'removed_at']);
        });
    }
};
