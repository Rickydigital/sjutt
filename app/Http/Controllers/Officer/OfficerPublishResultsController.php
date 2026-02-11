<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\ElectionResultPublish;
use App\Models\ElectionResultScope;
use App\Models\ElectionResultPosition;
use App\Models\ElectionResultCandidate;
use App\Models\ElectionVote;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class OfficerPublishResultsController extends Controller
{
    // ✅ adjust if your pivot tables are different
    private string $posFacultyPivot = 'election_position_faculty';
    private string $posProgramPivot = 'election_position_program';

    /**
     * Publish results snapshot for an election
     * - Creates snapshot rows for:
     *   - GLOBAL scope (one)
     *   - FACULTY scope (one per targeted faculty)
     *   - PROGRAM scope (one per targeted program)
     * - Under each scope creates position results + candidate results + winner
     */
    public function publish(Request $request, Election $election)
    {
        // Only allow publish when election is closed or already published (re-publish creates new version)
        abort_if(!in_array($election->status, ['closed', 'published'], true), 403, 'Election must be closed before publishing.');

        $officer = $this->currentOfficerStudent();
        abort_if(!$officer, 403, 'Officer not authenticated.');

        return DB::transaction(function () use ($request, $election, $officer) {

            // ------------------------------------------------------------
            // 1) Create publish header (versioned)
            // ------------------------------------------------------------
            $nextVersion = (int) ElectionResultPublish::where('election_id', $election->id)->max('version') + 1;

            $publish = ElectionResultPublish::create([
                'election_id'   => $election->id,
                'published_by'  => $officer->id, // ✅ Student ID (not User)
                'published_at'  => Carbon::now(),
                'version'       => $nextVersion,
                'notes'         => $request->input('notes'),
            ]);

            // ------------------------------------------------------------
            // 2) Load positions once
            // ------------------------------------------------------------
            /** @var \Illuminate\Support\Collection<int, ElectionPosition> $positions */
            $positions = ElectionPosition::query()
                ->where('election_id', $election->id)
                ->where('is_enabled', true)
                ->with(['definition'])
                ->orderByRaw("FIELD(scope_type, 'global','program','faculty')")
                ->orderBy('id')
                ->get();

            // ------------------------------------------------------------
            // 3) Determine all scope targets to publish
            //    - Global: always 1 scope row
            //    - Faculty: all faculties attached to any faculty position in this election
            //    - Program: all programs attached to any program position in this election
            // ------------------------------------------------------------
            $facultyIds = DB::table($this->posFacultyPivot . ' as p')
                ->join('election_positions as ep', 'ep.id', '=', 'p.election_position_id')
                ->where('ep.election_id', $election->id)
                ->where('ep.scope_type', 'faculty')
                ->distinct()
                ->pluck('p.faculty_id')
                ->filter()
                ->values()
                ->all();

            $programIds = DB::table($this->posProgramPivot . ' as p')
                ->join('election_positions as ep', 'ep.id', '=', 'p.election_position_id')
                ->where('ep.election_id', $election->id)
                ->where('ep.scope_type', 'program')
                ->distinct()
                ->pluck('p.program_id')
                ->filter()
                ->values()
                ->all();

            // ------------------------------------------------------------
            // 4) Create GLOBAL scope snapshot + its positions
            // ------------------------------------------------------------
            $globalEligible = Student::where('status', 'Active')->count();

            $globalScope = ElectionResultScope::create([
                'result_publish_id' => $publish->id,
                'scope_type'        => 'global',
                'faculty_id'        => null,
                'program_id'        => null,
                'eligible_students' => $globalEligible,
                'voters'            => $this->countDistinctVotersForScope($election->id, 'global', null, null),
                'turnout_percent'   => $globalEligible > 0
                    ? round(($this->countDistinctVotersForScope($election->id, 'global', null, null) / $globalEligible) * 100, 2)
                    : 0,
            ]);

            $globalPositions = $positions->where('scope_type', 'global')->values();
            foreach ($globalPositions as $position) {
                $this->publishOnePositionSnapshot(
                    electionId: $election->id,
                    scopeRow: $globalScope,
                    position: $position,
                    scopeType: 'global',
                    facultyId: null,
                    programId: null,
                    eligibleStudents: $globalEligible
                );
            }

            // ------------------------------------------------------------
            // 5) Create FACULTY scope snapshot per faculty + its positions
            // ------------------------------------------------------------
            if (!empty($facultyIds)) {
                foreach ($facultyIds as $facultyId) {

                    $eligible = Student::where('status', 'Active')
                        ->where('faculty_id', $facultyId)
                        ->count();

                    $voters = $this->countDistinctVotersForScope($election->id, 'faculty', $facultyId, null);

                    $scope = ElectionResultScope::create([
                        'result_publish_id' => $publish->id,
                        'scope_type'        => 'faculty',
                        'faculty_id'        => $facultyId,
                        'program_id'        => null,
                        'eligible_students' => $eligible,
                        'voters'            => $voters,
                        'turnout_percent'   => $eligible > 0 ? round(($voters / $eligible) * 100, 2) : 0,
                    ]);

                    // Only faculty positions that target this faculty
                    $facultyPositions = $positions->where('scope_type', 'faculty')->filter(function ($p) use ($facultyId) {
                        return DB::table($this->posFacultyPivot)
                            ->where('election_position_id', $p->id)
                            ->where('faculty_id', $facultyId)
                            ->exists();
                    })->values();

                    foreach ($facultyPositions as $position) {
                        $this->publishOnePositionSnapshot(
                            electionId: $election->id,
                            scopeRow: $scope,
                            position: $position,
                            scopeType: 'faculty',
                            facultyId: (int) $facultyId,
                            programId: null,
                            eligibleStudents: $eligible
                        );
                    }
                }
            }

            // ------------------------------------------------------------
            // 6) Create PROGRAM scope snapshot per program + its positions
            // ------------------------------------------------------------
            if (!empty($programIds)) {
                foreach ($programIds as $programId) {

                    $eligible = Student::where('status', 'Active')
                        ->where('program_id', $programId)
                        ->count();

                    $voters = $this->countDistinctVotersForScope($election->id, 'program', null, $programId);

                    $scope = ElectionResultScope::create([
                        'result_publish_id' => $publish->id,
                        'scope_type'        => 'program',
                        'faculty_id'        => null,
                        'program_id'        => $programId,
                        'eligible_students' => $eligible,
                        'voters'            => $voters,
                        'turnout_percent'   => $eligible > 0 ? round(($voters / $eligible) * 100, 2) : 0,
                    ]);

                    // Only program positions that target this program
                    $programPositions = $positions->where('scope_type', 'program')->filter(function ($p) use ($programId) {
                        return DB::table($this->posProgramPivot)
                            ->where('election_position_id', $p->id)
                            ->where('program_id', $programId)
                            ->exists();
                    })->values();

                    foreach ($programPositions as $position) {
                        $this->publishOnePositionSnapshot(
                            electionId: $election->id,
                            scopeRow: $scope,
                            position: $position,
                            scopeType: 'program',
                            facultyId: null,
                            programId: (int) $programId,
                            eligibleStudents: $eligible
                        );
                    }
                }
            }

            // Mark election as published (optional)
            $election->update(['status' => 'published']);

            return back()->with('success', "Results published successfully (version {$nextVersion}).");
        });
    }

    /**
     * Create one position snapshot under one scope row.
     * - Creates ElectionResultPosition
     * - Creates ElectionResultCandidate rows for ALL candidates in that position+scope
     * - Calculates winner + ranks
     * - % is based on eligible students (as you requested)
     */
    private function publishOnePositionSnapshot(
        int $electionId,
        ElectionResultScope $scopeRow,
        ElectionPosition $position,
        string $scopeType,
        ?int $facultyId,
        ?int $programId,
        int $eligibleStudents
    ): void {
        // Distinct voters for THIS position within THIS scope
        $positionVoters = $this->countDistinctVotersForPositionScope(
            electionId: $electionId,
            positionId: $position->id,
            scopeType: $scopeType,
            facultyId: $facultyId,
            programId: $programId
        );

        $turnoutPercent = $eligibleStudents > 0 ? round(($positionVoters / $eligibleStudents) * 100, 2) : 0;

        // Create position row first (winner filled later)
        $posSnap = ElectionResultPosition::create([
            'result_scope_id'     => $scopeRow->id,
            'election_position_id'=> $position->id,
            'position_name'       => $position->definition?->name ?? 'Position',
            'eligible_students'   => $eligibleStudents,
            'voters'              => $positionVoters,
            'turnout_percent'     => $turnoutPercent,
            'winner_candidate_id' => null,
        ]);

        // ------------------------------------------------------------
        // Candidates to include:
        // - GLOBAL: all candidates in that position
        // - FACULTY: candidates with faculty_id = facultyId
        // - PROGRAM: candidates with program_id = programId
        // ------------------------------------------------------------
        $candQuery = ElectionCandidate::query()
            ->where('election_position_id', $position->id)
            ->with(['student']);

        if ($scopeType === 'faculty' && $facultyId) {
            $candQuery->where('faculty_id', $facultyId);
        } elseif ($scopeType === 'program' && $programId) {
            $candQuery->where('program_id', $programId);
        }

        $candidates = $candQuery->get();

        // If no candidates, stop (still keep position snapshot)
        if ($candidates->isEmpty()) {
            return;
        }

        // Votes per candidate (filtered by scope voters)
        $voteCounts = $this->votesPerCandidateForPositionScope(
            electionId: $electionId,
            positionId: $position->id,
            scopeType: $scopeType,
            facultyId: $facultyId,
            programId: $programId
        ); // [candidate_id => votes]

        // Build candidate rows with vote_count even if 0
        $rows = $candidates->map(function ($c) use ($voteCounts, $eligibleStudents) {
            $s = $c->student;

            $votes = (int) ($voteCounts[$c->id] ?? 0);

            return (object) [
                'election_candidate_id' => $c->id,
                'candidate_name'        => trim(($s?->first_name ?? '') . ' ' . ($s?->last_name ?? '')),
                'candidate_reg_no'      => $s?->reg_no,
                'vote_count'            => $votes,
                'vote_percent'          => $eligibleStudents > 0 ? round(($votes / $eligibleStudents) * 100, 2) : 0,
            ];
        })->sortByDesc('vote_count')->values();

        // Assign ranks (ties share rank)
        $rank = 1;
        foreach ($rows as $i => $r) {
            if ($i > 0 && $r->vote_count < $rows[$i - 1]->vote_count) {
                $rank = $i + 1;
            }
            $r->rank = $rank;
        }

        // Winner is rank 1 if votes > 0 (you can remove >0 if you still want a winner at 0 votes)
        $winner = $rows->first();
        $winnerCandidateId = ($winner && $winner->vote_count > 0) ? $winner->election_candidate_id : null;

        // Store candidates
        foreach ($rows as $r) {
            ElectionResultCandidate::create([
                'result_position_id'    => $posSnap->id,
                'election_candidate_id' => $r->election_candidate_id,
                'candidate_name'        => $r->candidate_name ?: 'Unknown Student',
                'candidate_reg_no'      => $r->candidate_reg_no,
                'vote_count'            => $r->vote_count,
                'vote_percent'          => $r->vote_percent,
                'rank'                  => $r->rank,
                'is_winner'             => $winnerCandidateId && $r->election_candidate_id === $winnerCandidateId,
            ]);
        }

        // Update winner on position snapshot
        $posSnap->update([
            'winner_candidate_id' => $winnerCandidateId,
        ]);
    }

    /**
     * Count distinct voters across the whole election for a given scope type.
     * This is used for the scope header turnout (not per position).
     */
    private function countDistinctVotersForScope(int $electionId, string $scopeType, ?int $facultyId, ?int $programId): int
    {
        $q = ElectionVote::query()
            ->join('students as voter', 'voter.id', '=', 'election_votes.student_id')
            ->join('election_positions as ep', 'ep.id', '=', 'election_votes.election_position_id')
            ->where('election_votes.election_id', $electionId)
            ->where('voter.status', 'Active');

        if ($scopeType === 'global') {
            $q->where('ep.scope_type', 'global');
        } elseif ($scopeType === 'faculty' && $facultyId) {
            $q->where('ep.scope_type', 'faculty')
              ->where('voter.faculty_id', $facultyId);
        } elseif ($scopeType === 'program' && $programId) {
            $q->where('ep.scope_type', 'program')
              ->where('voter.program_id', $programId);
        } else {
            return 0;
        }

        return (int) $q->distinct('election_votes.student_id')->count('election_votes.student_id');
    }

    /**
     * Distinct voters for ONE position within ONE scope (this drives position turnout).
     */
    private function countDistinctVotersForPositionScope(
        int $electionId,
        int $positionId,
        string $scopeType,
        ?int $facultyId,
        ?int $programId
    ): int {
        $q = ElectionVote::query()
            ->join('students as voter', 'voter.id', '=', 'election_votes.student_id')
            ->where('election_votes.election_id', $electionId)
            ->where('election_votes.election_position_id', $positionId)
            ->where('voter.status', 'Active');

        if ($scopeType === 'faculty' && $facultyId) {
            $q->where('voter.faculty_id', $facultyId);
        } elseif ($scopeType === 'program' && $programId) {
            $q->where('voter.program_id', $programId);
        }
        // global -> no extra filter

        return (int) $q->distinct('election_votes.student_id')->count('election_votes.student_id');
    }

    /**
     * Vote counts per candidate for ONE position within ONE scope.
     * Returns array: [candidate_id => votes]
     */
    private function votesPerCandidateForPositionScope(
        int $electionId,
        int $positionId,
        string $scopeType,
        ?int $facultyId,
        ?int $programId
    ): array {
        $q = ElectionVote::query()
            ->join('students as voter', 'voter.id', '=', 'election_votes.student_id')
            ->where('election_votes.election_id', $electionId)
            ->where('election_votes.election_position_id', $positionId)
            ->where('voter.status', 'Active');

        if ($scopeType === 'faculty' && $facultyId) {
            $q->where('voter.faculty_id', $facultyId);
        } elseif ($scopeType === 'program' && $programId) {
            $q->where('voter.program_id', $programId);
        }

        return $q->selectRaw('election_votes.candidate_id, COUNT(*) as votes')
            ->groupBy('election_votes.candidate_id')
            ->pluck('votes', 'election_votes.candidate_id')
            ->toArray();
    }

    /**
     * Officer is a Student (not User).
     * We try multiple guards safely.
     */
private function currentOfficerStudent(): ?Student
{
    // ✅ your real guard
    if (auth()->guard('stuofficer')->check()) {
        $u = auth()->guard('stuofficer')->user();
        return $u instanceof Student ? $u : null;
    }

    // fallback (if default auth happens to be student)
    $u = Auth::user();
    return $u instanceof Student ? $u : null;
}

public function published(Election $election)
{
    // 1) Latest publish
    $publish = ElectionResultPublish::where('election_id', $election->id)
        ->orderByDesc('version')
        ->first();

    abort_if(!$publish, 404, 'No published results found for this election.');

    // 2) Scopes
    $scopes = ElectionResultScope::where('result_publish_id', $publish->id)->get();
    abort_if($scopes->isEmpty(), 404, 'Published results found, but scopes missing.');

    $globalScope   = $scopes->firstWhere('scope_type', 'global');
    $programScopes = $scopes->where('scope_type', 'program')->values();
    $facultyScopes = $scopes->where('scope_type', 'faculty')->values();
    $scopeIds = $scopes->pluck('id')->all();

    // 3) Positions (JOIN scopes to sort by scope_type)
    $positions = ElectionResultPosition::query()
    ->join('election_result_scopes as ers', 'ers.id', '=', 'election_result_positions.result_scope_id')
    ->whereIn('election_result_positions.result_scope_id', $scopeIds)
    ->select([
        'election_result_positions.*',
        'ers.scope_type as scope_type',
        'ers.program_id as program_id',
        'ers.faculty_id as faculty_id',
    ])
    ->orderByRaw("FIELD(ers.scope_type, 'global','program','faculty')")
    ->orderBy('election_result_positions.position_name')
    ->get();


    $positionIds = $positions->pluck('id')->all();

    // 4) Candidates (by result_position_id)
    $candidates = ElectionResultCandidate::whereIn('result_position_id', $positionIds)
        ->orderByDesc('vote_count')
        ->get()
        ->groupBy('result_position_id');

    $programMap = DB::table('programs')
    ->selectRaw('id, COALESCE(NULLIF(short_name,""), name) as label')
    ->pluck('label', 'id')
    ->toArray();

    $facultyMap = DB::table('faculties')->pluck('name', 'id')->toArray();


    // 5) Attach candidates + ranks (ties share rank)
    $positions = $positions->map(function ($position) use ($candidates) {
        $candList = $candidates->get($position->id, collect())->values();

        $rank = 1;
        foreach ($candList as $i => $cand) {
            if ($i > 0 && $cand->vote_count < $candList[$i - 1]->vote_count) {
                $rank = $i + 1;
            }
            $cand->rank = $rank;
        }

        $position->setRelation('candidates', $candList);
        return $position;
    });

   return view('officer.results.published', [
    'election'    => $election,
    'publish'     => $publish,
    'positions'   => $positions,
    'globalScope' => $globalScope ?? null,      // if you pass it
    'programScopes' => $programScopes ?? collect(),
    'facultyScopes' => $facultyScopes ?? collect(),
    'programMap'  => $programMap,
    'facultyMap'  => $facultyMap,
]);


}

   public function publishedPdf(Election $election, Request $request)
    {
        // 1) Latest publish
        $publish = ElectionResultPublish::where('election_id', $election->id)
            ->orderByDesc('version')
            ->first();

        abort_if(!$publish, 404, 'No published results found for this election.');

        // 2) Scopes
        $scopes = ElectionResultScope::where('result_publish_id', $publish->id)->get();
        abort_if($scopes->isEmpty(), 404, 'Published results found, but scopes missing.');

        $globalScope   = $scopes->firstWhere('scope_type', 'global');
        $programScopes = $scopes->where('scope_type', 'program')->values();
        $facultyScopes = $scopes->where('scope_type', 'faculty')->values();
        $scopeIds      = $scopes->pluck('id')->all();

        // maps for labels in headers
        $programMap = DB::table('programs')
            ->selectRaw('id, COALESCE(NULLIF(name,""), name) as label')
            ->pluck('label', 'id')
            ->toArray();

        $facultyMap = DB::table('faculties')->pluck('name', 'id')->toArray();

        // 3) Positions + include scope ids so we can group by scope
        $positions = ElectionResultPosition::query()
            ->join('election_result_scopes as ers', 'ers.id', '=', 'election_result_positions.result_scope_id')
            ->whereIn('election_result_positions.result_scope_id', $scopeIds)
            ->select([
                'election_result_positions.*',
                'ers.scope_type as scope_type',
                'ers.program_id as program_id',
                'ers.faculty_id as faculty_id',
            ])
            ->orderByRaw("FIELD(ers.scope_type, 'global','program','faculty')")
            ->orderBy('election_result_positions.position_name')
            ->get();

        $positionIds = $positions->pluck('id')->all();

        // 4) Candidates for those positions
        $candidatesByPos = ElectionResultCandidate::whereIn('result_position_id', $positionIds)
            ->orderByDesc('vote_count')
            ->get()
            ->groupBy('result_position_id');

        // attach candidates + ranks (ties share rank)
        $positions = $positions->map(function ($position) use ($candidatesByPos) {
            $candList = $candidatesByPos->get($position->id, collect())->values();

            $rank = 1;
            foreach ($candList as $i => $cand) {
                if ($i > 0 && (int)$cand->vote_count < (int)$candList[$i - 1]->vote_count) {
                    $rank = $i + 1;
                }
                $cand->rank = $rank;
            }

            $position->setRelation('candidates', $candList);
            return $position;
        });

        // 5) Group positions by scope row (result_scope_id) to print each scope on its own page
        $positionsByScopeId = $positions->groupBy('result_scope_id');

        // 6) Build PDF
        $pdf = Pdf::loadView('elections.published_pdf', [
            'election'            => $election,
            'publish'             => $publish,

            'globalScope'         => $globalScope,
            'programScopes'       => $programScopes,
            'facultyScopes'       => $facultyScopes,

            'positionsByScopeId'  => $positionsByScopeId,

            'programMap'          => $programMap,
            'facultyMap'          => $facultyMap,

            'generatedAt'         => now(),
        ])->setPaper('a4', 'portrait');

        $filename = 'published_results_' . str($election->title)->slug('_') . '_v' . $publish->version . '_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }


}
