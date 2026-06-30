<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElectionVotingController extends Controller
{
    public function index()
    {
        $student = auth('stuofficer')->user();

        // elections student can vote in (OPEN)
        $elections = Election::query()
            ->where('status', 'open')
            ->orderByDesc('id')
            ->get();

        // all position IDs already voted by this student (across all elections)
        $votedPositionIds = ElectionVote::query()
            ->where('student_id', $student->id)
            ->pluck('election_position_id')
            ->toArray();

        // Load eligible positions for each election
        $elections->load(['positions' => function ($q) use ($student, $votedPositionIds) {
            $q->where('is_enabled', true)
                ->whereNotIn('id', $votedPositionIds)
                ->where(function ($w) use ($student) {

                    // 1) GLOBAL -> everyone sees
                    $w->where(function ($g) use ($student) {
                    $g->where('scope_type', 'global')
                        ->where(function ($x) use ($student) {
                            $x->whereDoesntHave('programs')
                            ->whereDoesntHave('faculties')
                            ->orWhereHas('programs', fn ($p) =>
                                $p->where('programs.id', $student->program_id)
                            )
                            ->orWhereHas('faculties', fn ($f) =>
                                $f->where('faculties.id', $student->faculty_id)
                            );
                        });
                });

                    // 2) PROGRAM -> only if student's program is attached to position
                    $w->orWhere(function ($q3) use ($student) {
                        $q3->where('scope_type', 'program')
                            ->whereHas('programs', fn($p) => $p->where('programs.id', $student->program_id));
                    });

                    // 3) FACULTY -> only if student's faculty is attached to position
                    $w->orWhere(function ($q2) use ($student) {
                        $q2->where('scope_type', 'faculty')
                            ->whereHas('faculties', fn($f) => $f->where('faculties.id', $student->faculty_id));
                    });
                })
                // priority: global -> program -> faculty
                ->orderByRaw("FIELD(scope_type, 'global', 'program', 'faculty')")
                ->orderBy('id')
                ->with([
                    'definition',
                    // IMPORTANT: do NOT filter by student's scope here
                    // because GLOBAL position should show ALL candidates for that position
                    'candidates' => function ($c) {
                        $c->with([
                            'student.faculty',
                            'student.program',
                            'vice.student.faculty',
                            'vice.student.program',
                        ])
                            ->whereHas('student', fn($s) => $s->where('status', 'Active'));
                    },

                ]);
        }]);

        /**
         * Now filter candidates PER POSITION in PHP:
         * - global: show all candidates (no faculty/program filtering)
         * - faculty: only candidates with faculty_id == student's faculty_id
         * - program: only candidates with program_id == student's program_id
         */
        $elections->each(function ($election) use ($student) {
            $election->positions->each(function ($position) use ($student) {

                $filtered = $position->candidates->filter(function ($cand) use ($position, $student) {
                    return match ($position->scope_type) {
                        'global'  => true,
                        'faculty' => (int) $cand->faculty_id === (int) $student->faculty_id,
                        'program' => (int) $cand->program_id === (int) $student->program_id,
                        default   => false,
                    };
                })->values();

                $position->setRelation('candidates', $filtered);
            });

            // remove positions with zero candidates
            $election->setRelation(
                'positions',
                $election->positions
                    ->filter(fn($position) => $position->candidates->isNotEmpty())
                    ->values()
            );
        });

        // remove elections with no remaining votable positions
        $elections = $elections
            ->filter(fn($e) => $e->positions->isNotEmpty())
            ->values();

        return view('vote', compact('elections'));
    }

    public function store(Request $request)
    {
        $student = auth('stuofficer')->user();

        $validated = $request->validate([
            'election_position_id' => ['required', 'exists:election_positions,id'],
            'candidate_id'         => ['required', 'exists:election_candidates,id'],
            'form4_index'          => ['nullable', 'string'],
        ]);

       
        if (empty($validated['form4_index'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Your Form Four Index number is required to vote. Please update your app and try again.',
            ], 422);
        }
        if (!$student->form4_index || !hash_equals((string) $student->form4_index, (string) $validated['form4_index'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid Form Four Index number. Please check and try again.',
            ], 403);
        }

        $position = ElectionPosition::query()
            ->with(['election', 'faculties', 'programs'])
            ->where('id', $validated['election_position_id'])
            ->where('is_enabled', true)
            ->firstOrFail();

        $election = $position->election;

        abort_if(!$election || $election->status !== 'open', 403, 'Election is not open.');

        if (method_exists($election, 'isStillOpen')) {
            abort_if(!$election->isStillOpen(), 403, 'Voting time is closed.');
        }

        $candidate = $position->candidates()
            ->where('id', $validated['candidate_id'])
            ->firstOrFail();

        abort_if(!$candidate->is_approved, 403, 'Candidate is pending approval.');

        $eligible = match ($position->scope_type) {
            'global' => (
                !$position->programs()->exists() &&
                !$position->faculties()->exists()
            ) || (
                $student->program_id &&
                $position->programs()->where('programs.id', $student->program_id)->exists()
            ) || (
                $student->faculty_id &&
                $position->faculties()->where('faculties.id', $student->faculty_id)->exists()
            ),
            'faculty' => $student->faculty_id
                && $position->faculties()->where('faculties.id', $student->faculty_id)->exists(),
            'program' => $student->program_id
                && $position->programs()->where('programs.id', $student->program_id)->exists(),
            default => false,
        };

        abort_if(!$eligible, 403, 'You are not eligible to vote for this position.');

        if ($position->scope_type === 'faculty') {
            abort_if((int) $candidate->faculty_id !== (int) $student->faculty_id, 403, 'Candidate not in your faculty scope.');
        }

        if ($position->scope_type === 'program') {
            abort_if((int) $candidate->program_id !== (int) $student->program_id, 403, 'Candidate not in your program scope.');
        }

        return DB::transaction(function () use ($election, $position, $candidate, $student) {
            $already = ElectionVote::query()
                ->where('election_id', $election->id)
                ->where('election_position_id', $position->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->exists();

            abort_if($already, 422, 'You already voted for this position.');

            $hmac = hash_hmac('sha256', implode('|', [
                $election->id,
                $position->id,
                $candidate->id,
                $student->id,
            ]), config('vote.hmac_secret'));

            ElectionVote::create([
                'election_id'          => $election->id,
                'election_position_id' => $position->id,
                'candidate_id'         => $candidate->id,
                'student_id'           => $student->id,
                'vote_hmac'            => $hmac,
            ]);

            return back()->with('success', 'Vote submitted successfully.');
        });
    }
}
