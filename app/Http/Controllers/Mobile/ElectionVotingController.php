<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElectionVotingController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();

        $elections = Election::query()
            ->where('status', 'open')
            ->orderByDesc('id')
            ->get();

        $votedPositionIds = ElectionVote::query()
            ->where('student_id', $student->id)
            ->whereIn('election_id', $elections->pluck('id'))
            ->pluck('election_position_id')
            ->toArray();

        $elections->load(['positions' => function ($q) use ($student, $votedPositionIds) {
            $q->where('is_enabled', true)
                ->whereNotIn('id', $votedPositionIds)
                ->where(function ($w) use ($student) {
                    $w->where('scope_type', 'global')
                        ->orWhere(function ($q) use ($student) {
                            $q->where('scope_type', 'program')
                                ->whereHas('programs', fn ($p) =>
                                    $p->where('programs.id', $student->program_id)
                                );
                        })
                        ->orWhere(function ($q) use ($student) {
                            $q->where('scope_type', 'faculty')
                                ->whereHas('faculties', fn ($f) =>
                                    $f->where('faculties.id', $student->faculty_id)
                                );
                        });
                })
                ->orderByRaw("FIELD(scope_type, 'global', 'program', 'faculty')")
                ->orderBy('id')
                ->with([
                    'definition',
                    'candidates' => function ($c) {
                        $c->where('is_approved', true)
                            ->whereHas('student', fn ($s) => $s->where('status', 'Active'))
                            ->with([
                                'student.faculty',
                                'student.program',
                                'vice.student.faculty',
                                'vice.student.program',
                            ]);
                    },
                ]);
        }]);

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
        });

        $elections = $elections
            ->filter(fn ($e) => $e->positions->isNotEmpty())
            ->values();

        return response()->json([
            'status' => 'success',
            'message' => 'Available elections retrieved successfully.',
            'data' => [
                'elections' => $elections,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $student = $request->user();

        $validated = $request->validate([
            'election_position_id' => ['required', 'exists:election_positions,id'],
            'candidate_id'         => ['required', 'exists:election_candidates,id'],
        ]);

        return DB::transaction(function () use ($validated, $student) {
            $position = ElectionPosition::query()
                ->with(['election', 'faculties', 'programs'])
                ->where('id', $validated['election_position_id'])
                ->where('is_enabled', true)
                ->lockForUpdate()
                ->firstOrFail();

            $election = $position->election;

            if (!$election || $election->status !== 'open') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Election is not open.',
                ], 403);
            }

            if (method_exists($election, 'isStillOpen') && !$election->isStillOpen()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Voting time is closed.',
                ], 403);
            }

            $candidate = $position->candidates()
                ->where('id', $validated['candidate_id'])
                ->firstOrFail();

            if (!$candidate->is_approved) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Candidate is pending approval.',
                ], 403);
            }

            $eligible = $position->isStudentEligible($student);

            if (!$eligible) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not eligible to vote for this position.',
                ], 403);
            }

            if ($position->scope_type === 'faculty' &&
                (int) $candidate->faculty_id !== (int) $student->faculty_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Candidate not in your faculty scope.',
                ], 403);
            }

            if ($position->scope_type === 'program' &&
                (int) $candidate->program_id !== (int) $student->program_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Candidate not in your program scope.',
                ], 403);
            }

            $already = ElectionVote::query()
                ->where('election_id', $election->id)
                ->where('election_position_id', $position->id)
                ->where('student_id', $student->id)
                ->exists();

            if ($already) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already voted for this position.',
                ], 422);
            }

            $vote = ElectionVote::create([
                'election_id'          => $election->id,
                'election_position_id' => $position->id,
                'candidate_id'         => $candidate->id,
                'student_id'           => $student->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Vote submitted successfully.',
                'data' => [
                    'vote' => $vote,
                ],
            ], 201);
        });
    }
}