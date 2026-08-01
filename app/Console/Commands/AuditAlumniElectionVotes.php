<?php

namespace App\Console\Commands;

use App\Models\AlumniElection;
use App\Models\AlumniElectionVote;
use Illuminate\Console\Command;

class AuditAlumniElectionVotes extends Command
{
    protected $signature = 'alumni-election:audit-votes {alumni_election_id}';
    protected $description = 'Audit alumni election votes using HMAC integrity checks';

    public function handle(): int
    {
        $election = AlumniElection::findOrFail($this->argument('alumni_election_id'));

        $this->info("Auditing election: {$election->title} (ID: {$election->id})");

        $votes = AlumniElectionVote::with(['alumni', 'candidate', 'position'])
            ->where('alumni_election_id', $election->id)
            ->get();

        $this->info("Total votes: {$votes->count()}");

        $bad = [];
        $secret = config('vote.hmac_secret', config('app.key'));

        // Track per-position voter IDs to detect duplicates
        $votersByPosition = [];

        foreach ($votes as $vote) {
            $issues = [];

            // HMAC integrity check
            $expectedHmac = hash_hmac('sha256', implode('|', [
                $vote->alumni_election_id,
                $vote->alumni_election_position_id,
                $vote->candidate_id,
                $vote->alumni_id,
            ]), $secret);

            if (!hash_equals((string) $expectedHmac, (string) $vote->vote_hmac)) {
                $issues[] = 'INVALID_HMAC';
            }

            if (!$vote->alumni) {
                $issues[] = 'ALUMNI_NOT_FOUND';
            }

            if (!$vote->candidate) {
                $issues[] = 'CANDIDATE_NOT_FOUND';
            }

            if (!$vote->position) {
                $issues[] = 'POSITION_NOT_FOUND';
            }

            if ($vote->candidate && $vote->position) {
                if ((int) $vote->candidate->alumni_election_position_id !== (int) $vote->alumni_election_position_id) {
                    $issues[] = 'CANDIDATE_NOT_IN_POSITION';
                }
            }

            // Duplicate voter check per position
            $key = "{$vote->alumni_election_position_id}:{$vote->alumni_id}";
            if (isset($votersByPosition[$key])) {
                $issues[] = 'DUPLICATE_VOTER';
            } else {
                $votersByPosition[$key] = $vote->id;
            }

            if (!empty($issues)) {
                $bad[] = [
                    'vote_id'     => $vote->id,
                    'alumni_id'   => $vote->alumni_id,
                    'position_id' => $vote->alumni_election_position_id,
                    'candidate_id'=> $vote->candidate_id,
                    'issues'      => implode(', ', $issues),
                ];
            }
        }

        if (empty($bad)) {
            $this->info('✅ All votes are valid — no integrity issues found.');
            return self::SUCCESS;
        }

        $this->table(
            ['Vote ID', 'Alumni ID', 'Position ID', 'Candidate ID', 'Issues'],
            $bad
        );

        $this->error(count($bad) . ' invalid/suspicious vote(s) found.');

        return self::FAILURE;
    }
}
