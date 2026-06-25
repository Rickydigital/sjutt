<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('election_votes', function (Blueprint $table) {
            $table->string('vote_hmac', 64)->nullable()->after('student_id');
        });

        // Backfill HMAC for all existing votes so they remain valid after this feature is enabled.
        $secret = env('VOTE_HMAC_SECRET', '');

        if ($secret !== '') {
            DB::table('election_votes')->orderBy('id')->chunk(500, function ($votes) use ($secret) {
                foreach ($votes as $vote) {
                    $payload = implode('|', [
                        $vote->election_id,
                        $vote->election_position_id,
                        $vote->candidate_id,
                        $vote->student_id,
                    ]);
                    DB::table('election_votes')
                        ->where('id', $vote->id)
                        ->update(['vote_hmac' => hash_hmac('sha256', $payload, $secret)]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('election_votes', function (Blueprint $table) {
            $table->dropColumn('vote_hmac');
        });
    }
};
