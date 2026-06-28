<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionPosition;
use App\Models\ElectionResultPublish;
use App\Models\ElectionResultScope;
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
})
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
            'form4_index'          => ['nullable', 'string'],
        ]);

        // TODO: re-enable form4_index verification once data is confirmed clean
        // if (empty($validated['form4_index'])) {
        //     return response()->json([
        //         'status'  => 'error',
        //         'message' => 'Your Form Four Index number is required to vote. Please update your app and try again.',
        //     ], 422);
        // }
        // if (!$student->form4_index || !hash_equals((string) $student->form4_index, (string) $validated['form4_index'])) {
        //     return response()->json([
        //         'status'  => 'error',
        //         'message' => 'Invalid Form Four Index number. Please check and try again.',
        //     ], 403);
        // }

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

            $hmac = hash_hmac('sha256', implode('|', [
                $election->id,
                $position->id,
                $candidate->id,
                $student->id,
            ]), config('vote.hmac_secret'));

            $vote = ElectionVote::create([
                'election_id'          => $election->id,
                'election_position_id' => $position->id,
                'candidate_id'         => $candidate->id,
                'student_id'           => $student->id,
                'vote_hmac'            => $hmac,
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

    public function myVotes(Request $request)
    {
        $student = $request->user();

        $votedPositionIds = ElectionVote::query()
            ->where('student_id', $student->id)
            ->pluck('election_position_id')
            ->toArray();

        return response()->json([
            'status' => 'success',
            'data' => [
                'voted_position_ids' => $votedPositionIds,
            ],
        ]);
    }

    public function results(Request $request, Election $election)
    {
        if ($election->status !== 'published') {
            return response()->json([
                'status' => 'error',
                'message' => 'Results are not published yet.',
            ], 403);
        }

        $publish = ElectionResultPublish::where('election_id', $election->id)
            ->orderByDesc('version')
            ->first();

        if (!$publish) {
            return response()->json([
                'status' => 'error',
                'message' => 'No published results found.',
            ], 404);
        }

        $scopes = ElectionResultScope::where('result_publish_id', $publish->id)
            ->with([
                'positions' => function ($q) {
                    $q->orderByRaw("FIELD(result_scope_id, result_scope_id)")
                        ->with([
                            'candidates' => fn ($c) => $c->orderByDesc('vote_count'),
                        ]);
                },
                'faculty:id,name',
                'program:id,name',
            ])
            ->get();

        $formatted = $scopes->map(function ($scope) {
            return [
                'scope_type'         => $scope->scope_type,
                'faculty'            => $scope->faculty ? ['id' => $scope->faculty->id, 'name' => $scope->faculty->name] : null,
                'program'            => $scope->program ? ['id' => $scope->program->id, 'name' => $scope->program->name] : null,
                'eligible_students'  => $scope->eligible_students,
                'voters'             => $scope->voters,
                'turnout_percent'    => (float) $scope->turnout_percent,
                'positions'          => $scope->positions->map(function ($pos) {
                    return [
                        'position_name'     => $pos->position_name,
                        'eligible_students' => $pos->eligible_students,
                        'voters'            => $pos->voters,
                        'turnout_percent'   => (float) $pos->turnout_percent,
                        'candidates'        => $pos->candidates->map(function ($cand) {
                            return [
                                'candidate_name'   => $cand->candidate_name,
                                'candidate_reg_no' => $cand->candidate_reg_no,
                                'vote_count'       => $cand->vote_count,
                                'vote_percent'     => (float) $cand->vote_percent,
                                'rank'             => $cand->rank,
                                'is_winner'        => (bool) $cand->is_winner,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'election'     => [
                    'id'     => $election->id,
                    'title'  => $election->title,
                    'status' => $election->status,
                ],
                'published_at' => $publish->published_at,
                'version'      => $publish->version,
                'notes'        => $publish->notes,
                'scopes'       => $formatted,
            ],
        ]);
    }

    public function elections(Request $request)
    {
        $status = $request->query('status');

        $query = Election::query()->orderByDesc('id');

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['open', 'closed', 'published']);
        }

        $elections = $query->get(['id', 'title', 'start_date', 'end_date', 'open_time', 'close_time', 'status']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'elections' => $elections,
            ],
        ]);
    }
}