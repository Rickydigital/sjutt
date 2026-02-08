<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionCandidate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElectionCandidateApprovalController extends Controller
{
    /**
     * Show candidates for a specific election (grouped by position)
     */
    public function index(Election $election, Request $request)
    {
        $query = ElectionCandidate::query()
            ->whereHas('electionPosition', fn ($q) => $q->where('election_id', $election->id))
            ->with([
                'student.faculty',
                'student.program',
                'electionPosition.definition',
                'electionPosition.election',
            ])
            ->latest();

        // filter: pending/approved/all
        $status = $request->get('status', 'pending'); // pending | approved | all
        if ($status === 'pending') {
            $query->where('is_approved', false);
        } elseif ($status === 'approved') {
            $query->where('is_approved', true);
        }

        // search by name/reg
        if ($search = trim((string) $request->get('q'))) {
            $query->whereHas('student', function ($s) use ($search) {
                $s->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('reg_no', 'like', "%{$search}%");
            });
        }

        $candidates = $query->get()->groupBy('election_position_id');

        // positions to show headers nicely
        $positions = $election->positions()
            ->with('definition')
            ->orderBy('id')
            ->get();

        $stats = [
            'total'    => ElectionCandidate::whereHas('electionPosition', fn($q) => $q->where('election_id', $election->id))->count(),
            'approved' => ElectionCandidate::whereHas('electionPosition', fn($q) => $q->where('election_id', $election->id))->where('is_approved', true)->count(),
            'pending'  => ElectionCandidate::whereHas('electionPosition', fn($q) => $q->where('election_id', $election->id))->where('is_approved', false)->count(),
        ];

        return view('elections.candidates-approval', compact('election', 'positions', 'candidates', 'stats', 'status'));
    }

    /**
     * Approve ONE candidate
     */
    public function approve(Election $election, ElectionCandidate $candidate)
    {
        abort_if($candidate->electionPosition?->election_id != $election->id, 404);

        $candidate->update(['is_approved' => true]);

        return back()->with('success', 'Candidate approved.');
    }

    /**
     * Reject/Unapprove ONE candidate (optional)
     */
    public function unapprove(Election $election, ElectionCandidate $candidate)
    {
        abort_if($candidate->electionPosition?->election_id != $election->id, 404);

        $candidate->update(['is_approved' => false]);

        return back()->with('success', 'Candidate moved back to pending.');
    }

    /**
     * Approve ALL pending candidates in this election
     */
    public function approveAll(Election $election)
    {
        DB::transaction(function () use ($election) {
            ElectionCandidate::query()
                ->whereHas('electionPosition', fn ($q) => $q->where('election_id', $election->id))
                ->where('is_approved', false)
                ->update(['is_approved' => true]);
        });

        return back()->with('success', 'All pending candidates approved for this election.');
    }

    public function exportPdf(Election $election, Request $request)
    {
        // you can export approved only by default
        $onlyApproved = $request->boolean('approved', true);

        $positions = $election->positions()
            ->with([
                'definition',
                'candidates' => function ($q) use ($onlyApproved) {
                    $q->with(['student.faculty', 'student.program'])
                      ->whereHas('student', fn($s) => $s->where('status', 'Active'));

                    if ($onlyApproved) {
                        $q->where('is_approved', true);
                    }
                }
            ])
            ->where('is_enabled', true)
            ->get();

        // group positions by scope in the order you want
        $priority = ['global' => 1, 'program' => 2, 'faculty' => 3];
        $positions = $positions->sortBy(fn($p) => $priority[$p->scope_type] ?? 99)->values();
        $grouped = $positions->groupBy('scope_type');

        $pdf = Pdf::loadView('elections.candidates-pdf', [
            'election' => $election,
            'grouped'  => $grouped,
            'onlyApproved' => $onlyApproved,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('candidates_' . str_replace(' ', '_', strtolower($election->title)) . '.pdf');
    }
}
