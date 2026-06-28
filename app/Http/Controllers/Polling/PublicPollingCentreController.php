<?php

namespace App\Http\Controllers\Polling;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use App\Models\PollingCentre;
use App\Models\PollingCentreSession;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicPollingCentreController extends Controller
{
    private function centreByToken(string $token): PollingCentre
    {
        $centre = PollingCentre::with('election')
            ->where('public_token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_if(!$centre->isUsable(), 403, 'Polling centre is not active or election is not open.');

        return $centre;
    }

    public function show(Request $request, string $token)
    {
        $centre = $this->centreByToken($token);

        $request->session()->forget([
            'polling_mode',
            'polling_centre_id',
            'polling_session_id',
            'polling_session_token',
        ]);

        return view('polling.public.start', compact('token', 'centre'));
    }

   public function verifyRegNo(Request $request, string $token)
{
    $centre = $this->centreByToken($token);

    $validated = $request->validate([
        'reg_no' => 'required|string',
    ]);

    $regNo = trim($validated['reg_no']);

    $student = Student::where('reg_no', $regNo)
        ->where('status', 'Active')
        ->first();

    if (!$student) {
        PollingCentreSession::create([
            'polling_centre_id' => $centre->id,
            'election_id'       => $centre->election_id,
            'reg_no'            => $regNo,
            'status'            => 'failed',
            'ip_address'        => $request->ip(),
            'user_agent'        => $request->userAgent(),
        ]);

        return back()
            ->withErrors(['reg_no' => 'Student not found or not active.'])
            ->withInput();
    }

    $plainSessionToken = Str::random(80);

    $session = PollingCentreSession::create([
        'polling_centre_id'  => $centre->id,
        'election_id'        => $centre->election_id,
        'student_id'         => $student->id,
        'reg_no'             => $student->reg_no,
        'status'             => 'identity_verified',
        'session_token_hash' => hash('sha256', $plainSessionToken),
        'expires_at'         => now()->addMinutes(15),
        'ip_address'         => $request->ip(),
        'user_agent'         => $request->userAgent(),
    ]);

    session([
        'polling_mode'          => true,
        'polling_centre_id'     => $centre->id,
        'polling_session_id'    => $session->id,
        'polling_session_token' => $plainSessionToken,
    ]);

    return redirect()->route('polling.public.vote', $token);
}

    public function verifyIdentity(Request $request, string $token)
    {
        $centre = $this->centreByToken($token);

        $validated = $request->validate([
            'session_token' => 'required|string',
            'form4_index'   => 'required|string',
            'last_name'     => 'required|string',
        ]);

        $session = PollingCentreSession::where('polling_centre_id', $centre->id)
            ->where('session_token_hash', hash('sha256', $validated['session_token']))
            ->where('status', 'reg_verified')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $student = Student::findOrFail($session->student_id);

        $form4Ok = strtolower(trim((string) $student->form4_index)) === strtolower(trim($validated['form4_index']));
        $lastNameOk = strtolower(trim((string) $student->last_name)) === strtolower(trim($validated['last_name']));

        if (!$form4Ok || !$lastNameOk) {
            $session->update([
                'form4_index' => $validated['form4_index'],
                'last_name'   => $validated['last_name'],
                'status'      => 'failed',
            ]);

            return redirect()
                ->route('polling.public.show', $token)
                ->withErrors(['identity' => 'Verification failed. Please try again.']);
        }

        $session->update([
            'form4_index' => $validated['form4_index'],
            'last_name'   => $validated['last_name'],
            'status'      => 'identity_verified',
        ]);

        session([
            'polling_mode'          => true,
            'polling_centre_id'     => $centre->id,
            'polling_session_id'    => $session->id,
            'polling_session_token' => $validated['session_token'],
        ]);

        return redirect()->route('polling.public.vote', $token);
    }

   public function votePage(Request $request, string $token)
{
    $centre = $this->centreByToken($token);
    $session = $this->currentPollingSession($centre);
    $student = Student::findOrFail($session->student_id);

    $elections = Election::query()
        ->where('id', $centre->election_id)
        ->where('status', 'open')
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
            $filteredCandidates = $position->candidates->filter(function ($candidate) use ($position, $student) {
                return match ($position->scope_type) {
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

                    'faculty' => (int) $candidate->faculty_id === (int) $student->faculty_id,

                    'program' => (int) $candidate->program_id === (int) $student->program_id,

                    default => false,
                };
            })->values();

            $position->setRelation('candidates', $filteredCandidates);
        });

        // Remove positions that have zero candidates after filtering
        $election->setRelation(
            'positions',
            $election->positions
                ->filter(fn ($position) => $position->candidates->isNotEmpty())
                ->values()
        );
    });

    // Remove elections that have no votable positions
    $elections = $elections
        ->filter(fn ($election) => $election->positions->isNotEmpty())
        ->values();

    // If no remaining position with candidates, auto finish session
    if ($elections->isEmpty()) {
        $this->completePollingSession($request, $centre);

        return redirect()
            ->route('polling.public.show', $token)
            ->with('success', 'Voting completed. No remaining candidate positions. Next student may continue.');
    }

    return view('polling.public.vote', compact(
        'token',
        'centre',
        'student',
        'session',
        'elections'
    ));
}

    public function storeVote(Request $request, string $token)
    {
        $centre = $this->centreByToken($token);
        $session = $this->currentPollingSession($centre);
        $student = Student::findOrFail($session->student_id);

        $validated = $request->validate([
            'election_position_id' => ['required', 'exists:election_positions,id'],
            'candidate_id'         => ['required', 'exists:election_candidates,id'],
        ]);

        return DB::transaction(function () use ($request, $validated, $student, $centre, $session, $token) {
            $position = ElectionPosition::query()
                ->with(['election', 'faculties', 'programs'])
                ->where('id', $validated['election_position_id'])
                ->where('is_enabled', true)
                ->lockForUpdate()
                ->firstOrFail();

            $election = $position->election;

            abort_if(!$election || (int) $election->id !== (int) $centre->election_id, 403, 'Invalid election for this polling centre.');
            abort_if($election->status !== 'open', 403, 'Election is not open.');

            if (method_exists($election, 'isStillOpen')) {
                abort_if(!$election->isStillOpen(), 403, 'Voting time is closed.');
            }

            abort_if($student->status !== 'Active', 403, 'Only active students can vote.');

            $candidate = $position->candidates()
                ->where('id', $validated['candidate_id'])
                ->firstOrFail();

            abort_if(!$candidate->is_approved, 403, 'Candidate is pending approval.');

            $eligible = match ($position->scope_type) {
                'global' => true,
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

            $session->increment('votes_cast');

            if ($this->remainingPositionsCount($election, $student) === 0) {
                $this->completePollingSession($request, $centre);

                return redirect()
                    ->route('polling.public.show', $token)
                    ->with('success', 'Voting completed. Next student may continue.');
            }

            return redirect()
                ->route('polling.public.vote', $token)
                ->with('success', 'Vote submitted successfully. Continue to the next position.');
        });
    }

    public function finish(Request $request, string $token)
    {
        $centre = $this->centreByToken($token);

        $this->completePollingSession($request, $centre);

        return redirect()
            ->route('polling.public.show', $token)
            ->with('success', 'Session completed. Next student may continue.');
    }

    private function currentPollingSession(PollingCentre $centre): PollingCentreSession
    {
        $sessionId = session('polling_session_id');

        abort_if(!$sessionId, 403, 'Polling session expired.');

        return PollingCentreSession::where('id', $sessionId)
            ->where('polling_centre_id', $centre->id)
            ->where('status', 'identity_verified')
            ->where('expires_at', '>', now())
            ->firstOrFail();
    }

    private function completePollingSession(Request $request, PollingCentre $centre): void
    {
        $sessionId = session('polling_session_id');

        if ($sessionId) {
            $session = PollingCentreSession::where('id', $sessionId)
                ->where('polling_centre_id', $centre->id)
                ->first();

            if ($session && $session->status !== 'completed') {
                $session->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);

                $centre->increment('successful_sessions');
            }
        }

        $request->session()->forget([
            'polling_mode',
            'polling_centre_id',
            'polling_session_id',
            'polling_session_token',
        ]);

        $request->session()->regenerateToken();
    }

    private function remainingPositionsCount(Election $election, Student $student): int
    {
        return ElectionPosition::query()
            ->where('election_id', $election->id)
            ->where('is_enabled', true)
            ->whereDoesntHave('votes', function ($q) use ($student, $election) {
                $q->where('student_id', $student->id)
                    ->where('election_id', $election->id);
            })
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
            ->count();
    }
}