<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use Illuminate\Http\Request;

class OfficerDashboardController extends Controller
{
    public function index()
    {
        $officer = auth('stuofficer')->user(); // your officer guard

        /**
         * IMPORTANT:
         * This assumes you have a relationship that returns elections assigned to this officer.
         * Example: $officer->assignedElections()
         *
         * If your project uses a different relationship, replace the query below
         * with your correct one.
         */

        // ✅ elections assigned to officer
        $electionsQuery = Election::whereHas('generalOfficers', fn($q) => $q->where('students.id', $officer->id));


        $assignedElections = (clone $electionsQuery)
            ->latest()
            ->take(6)
            ->get();

        $electionIds = (clone $electionsQuery)->pluck('id')->toArray();

        // ✅ counts
        $stats = [
            'assigned' => count($electionIds),
            'open'     => Election::whereIn('id', $electionIds)->where('status', 'open')->count(),
            'closed'   => Election::whereIn('id', $electionIds)->where('status', 'closed')->count(),
            'draft'    => Election::whereIn('id', $electionIds)->where('status', 'draft')->count(),
            'positions'=> ElectionPosition::whereIn('election_id', $electionIds)->count(),
            'candidates'=> ElectionCandidate::whereHas('electionPosition', fn($q) => $q->whereIn('election_id', $electionIds))->count(),
        ];

        // ✅ recent candidates (from officer elections)
        $recentCandidates = ElectionCandidate::query()
            ->whereHas('electionPosition', fn ($q) => $q->whereIn('election_id', $electionIds))
            ->with(['student.faculty', 'student.program', 'electionPosition.definition', 'electionPosition.election'])
            ->latest()
            ->take(8)
            ->get();

        // ✅ recent positions (from officer elections)
        $recentPositions = ElectionPosition::query()
            ->whereIn('election_id', $electionIds)
            ->with(['definition', 'election'])
            ->latest()
            ->take(8)
            ->get();

        return view('officer.dashboard', compact(
            'officer',
            'stats',
            'assignedElections',
            'recentCandidates',
            'recentPositions'
        ));
    }
}
