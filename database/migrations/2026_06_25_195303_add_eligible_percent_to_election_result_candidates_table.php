<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('election_result_candidates', function (Blueprint $table) {
            $table->decimal('eligible_percent', 8, 2)
                ->default(0)
                ->after('vote_percent');
        });
    }

    public function down(): void
    {
        Schema::table('election_result_candidates', function (Blueprint $table) {
            $table->dropColumn('eligible_percent');
        });
    }
};