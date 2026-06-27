<?php

namespace App\Http\Controllers\Polling;

use App\Http\Controllers\Controller;
use App\Models\PollingCentre;
use App\Models\PollingCentreSession;
use App\Models\Student;
use Illuminate\Http\Request;
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

    public function show(string $token)
    {
        $centre = $this->centreByToken($token);

        return view('polling.public.start', [
            'token' => $token,
            'centre' => $centre,
        ]);
    }

    public function verifyRegNo(Request $request, string $token)
    {
        $centre = $this->centreByToken($token);

        $validated = $request->validate([
            'reg_no' => 'required|string',
        ]);

        $student = Student::where('reg_no', trim($validated['reg_no']))
            ->where('status', 'Active')
            ->first();

        if (!$student) {
            PollingCentreSession::create([
                'polling_centre_id' => $centre->id,
                'election_id' => $centre->election_id,
                'reg_no' => $validated['reg_no'],
                'status' => 'failed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->withErrors([
                'reg_no' => 'Student not found or not active.',
            ])->withInput();
        }

        $plainSessionToken = Str::random(80);

        $session = PollingCentreSession::create([
            'polling_centre_id' => $centre->id,
            'election_id' => $centre->election_id,
            'student_id' => $student->id,
            'reg_no' => $student->reg_no,
            'status' => 'reg_verified',
            'session_token_hash' => hash('sha256', $plainSessionToken),
            'expires_at' => now()->addMinutes(15),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('polling.public.verify-identity', [
            'token' => $token,
            'centre' => $centre,
            'session_token' => $plainSessionToken,
            'student' => $student,
            'masked_name' => substr($student->first_name, 0, 1) . '*** ' . substr($student->last_name, 0, 1) . '***',
        ]);
    }

    public function verifyIdentity(Request $request, string $token)
    {
        $centre = $this->centreByToken($token);

        $validated = $request->validate([
            'session_token' => 'required|string',
            'form4_index' => 'required|string',
            'last_name' => 'required|string',
        ]);

        $session = PollingCentreSession::where('polling_centre_id', $centre->id)
            ->where('session_token_hash', hash('sha256', $validated['session_token']))
            ->where('status', 'reg_verified')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $student = Student::findOrFail($session->student_id);

        $form4Ok = strtolower(trim($student->form4_index)) === strtolower(trim($validated['form4_index']));
        $lastNameOk = strtolower(trim($student->last_name)) === strtolower(trim($validated['last_name']));

        if (!$form4Ok || !$lastNameOk) {
            $session->update([
                'form4_index' => $validated['form4_index'],
                'last_name' => $validated['last_name'],
                'status' => 'failed',
            ]);

            return redirect()
                ->route('polling.public.show', $token)
                ->withErrors(['identity' => 'Verification failed. Please try again.']);
        }

        $session->update([
            'form4_index' => $validated['form4_index'],
            'last_name' => $validated['last_name'],
            'status' => 'identity_verified',
        ]);

        // Login student only for this polling-centre browser session
        auth('stuofficer')->login($student);

        session([
            'polling_centre_id' => $centre->id,
            'polling_session_id' => $session->id,
            'polling_session_token' => $validated['session_token'],
        ]);

        return redirect()->route('student.vote.index');
    }

    public function finish(Request $request, string $token)
    {
        $centre = $this->centreByToken($token);

        $sessionId = session('polling_session_id');

        if ($sessionId) {
            $session = PollingCentreSession::where('id', $sessionId)
                ->where('polling_centre_id', $centre->id)
                ->first();

            if ($session) {
                $session->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $centre->increment('successful_sessions');
            }
        }

        auth('stuofficer')->logout();

        $request->session()->forget([
            'polling_centre_id',
            'polling_session_id',
            'polling_session_token',
        ]);

        $request->session()->regenerateToken();

        return redirect()
            ->route('polling.public.show', $token)
            ->with('success', 'Session completed. Next student may continue.');
    }
}