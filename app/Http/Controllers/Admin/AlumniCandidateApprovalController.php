<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Models\AlumniElection;
use App\Models\AlumniElectionCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlumniCandidateApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware('permission:approve alumni candidates');
    }

    public function index(AlumniElection $alumniElection)
    {
        $this->authorizeOfficerAccess($alumniElection);

        $candidates = $alumniElection->candidates()
            ->with(['alumni','position','sponsors'])
            ->latest()
            ->paginate(25);

        return view('alumni-elections.candidates', compact('alumniElection','candidates'));
    }

    public function approve(AlumniElectionCandidate $candidate)
    {
        $this->authorizeOfficerAccess($candidate->election);

        $position = $candidate->position;
        if ($position->max_candidates) {
            $approvedCount = $position->candidates()->where('application_status', 'approved')->count();
            if ($approvedCount >= $position->max_candidates) {
                return back()->with('error', 'Maximum approved candidates reached for this position.');
            }
        }

        $candidate->update([
            'application_status' => 'approved',
            'rejection_reason' => null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Candidate approved.');
    }

    public function reject(Request $request, AlumniElectionCandidate $candidate)
    {
        $this->authorizeOfficerAccess($candidate->election);

        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        $candidate->update([
            'application_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Candidate rejected.');
    }

    private function authorizeOfficerAccess(AlumniElection $election): void
    {
        $user = Auth::user();
        if (!$user || !$election->canBeOverseenBy($user)) {
            abort(403, 'You are not assigned to oversee this election.');
        }
    }
}
