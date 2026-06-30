<?php

namespace App\Console\Commands;

use App\Models\Election;
use App\Models\ElectionVote;
use Illuminate\Console\Command;

class AuditElectionVotes extends Command
{
    protected $signature = 'election:audit-votes {election_id}';
    protected $description = 'Audit election votes using HMAC and eligibility rules';

    public function handle()
    {
        $election = Election::findOrFail($this->argument('election_id'));

        $votes = ElectionVote::with([
            'voter',
            'candidate',
            'electionPosition.faculties',
            'electionPosition.programs',
        ])
            ->where('election_id', $election->id)
            ->get();

        $bad = [];

        foreach ($votes as $vote) {
            $issues = [];

            $expectedHmac = hash_hmac('sha256', implode('|', [
                $vote->election_id,
                $vote->election_position_id,
                $vote->candidate_id,
                $vote->student_id,
            ]), config('vote.hmac_secret'));

            if (!hash_equals((string) $expectedHmac, (string) $vote->vote_hmac)) {
                $issues[] = 'INVALID_HMAC';
            }

            if (!$vote->voter) {
                $issues[] = 'STUDENT_NOT_FOUND';
            }

            if (!$vote->candidate) {
                $issues[] = 'CANDIDATE_NOT_FOUND';
            }

            if (!$vote->electionPosition) {
                $issues[] = 'POSITION_NOT_FOUND';
            }

            if ($vote->candidate && $vote->electionPosition) {
                if ((int) $vote->candidate->election_position_id !== (int) $vote->election_position_id) {
                    $issues[] = 'CANDIDATE_NOT_IN_POSITION';
                }
            }

            if ($vote->voter && $vote->electionPosition) {
                $position = $vote->electionPosition;
                $student = $vote->voter;

                if ($position->scope_type === 'faculty') {
                    $allowed = $position->faculties->pluck('id')->contains((int) $student->faculty_id);

                    if (!$allowed) {
                        $issues[] = 'STUDENT_NOT_IN_ALLOWED_FACULTY';
                    }

                    if ($vote->candidate && (int) $vote->candidate->faculty_id !== (int) $student->faculty_id) {
                        $issues[] = 'CANDIDATE_NOT_IN_STUDENT_FACULTY';
                    }
                }

                if ($position->scope_type === 'program') {
                    $allowed = $position->programs->pluck('id')->contains((int) $student->program_id);

                    if (!$allowed) {
                        $issues[] = 'STUDENT_NOT_IN_ALLOWED_PROGRAM';
                    }

                    if ($vote->candidate && (int) $vote->candidate->program_id !== (int) $student->program_id) {
                        $issues[] = 'CANDIDATE_NOT_IN_STUDENT_PROGRAM';
                    }
                }
            }

            if (!empty($issues)) {
                $bad[] = [
                    'vote_id' => $vote->id,
                    'student_id' => $vote->student_id,
                    'position_id' => $vote->election_position_id,
                    'candidate_id' => $vote->candidate_id,
                    'issues' => implode(', ', $issues),
                ];
            }
        }

        if (empty($bad)) {
            $this->info('All votes are valid.');
            return self::SUCCESS;
        }

        $this->table(
            ['Vote ID', 'Student ID', 'Position ID', 'Candidate ID', 'Issues'],
            $bad
        );

        $this->error(count($bad) . ' invalid/suspicious vote(s) found.');

        return self::FAILURE;
    }
}