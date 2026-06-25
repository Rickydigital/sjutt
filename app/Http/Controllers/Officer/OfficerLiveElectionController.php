<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OfficerLiveElectionController extends Controller
{
    public function show(Election $election)
    {
        $this->authorizeOfficer($election);

        return view('officer.elections.live-command', compact('election'));
    }

    public function data(Election $election)
    {
        $this->authorizeOfficer($election);

        $eligible = Student::where('status', 'Active')->count();

        $uniqueVoters = ElectionVote::where('election_id', $election->id)
            ->distinct('student_id')
            ->count('student_id');

        $totalVotes = ElectionVote::where('election_id', $election->id)->count();

        $remaining = max($eligible - $uniqueVoters, 0);

        $turnout = $eligible > 0
            ? round(($uniqueVoters / $eligible) * 100, 2)
            : 0;

        $remainingPercent = $eligible > 0
            ? round(($remaining / $eligible) * 100, 2)
            : 0;

        $closeAt = null;

        if ($election->end_date && $election->close_time) {
            $closeAt = Carbon::parse(
                $election->end_date->format('Y-m-d') . ' ' . $election->close_time
            );
        }

        return response()->json([
            'election' => [
                'id' => $election->id,
                'title' => $election->title,
                'status' => $election->status,
                'close_at' => $closeAt?->toIso8601String(),
                'server_time' => now()->toIso8601String(),
            ],

            'summary' => [
                'eligible' => $eligible,
                'unique_voters' => $uniqueVoters,
                'total_votes' => $totalVotes,
                'remaining' => $remaining,
                'turnout' => $turnout,
                'remaining_percent' => $remainingPercent,
                'time_remaining_percent' => $this->timeRemainingPercent($election),
            ],

            'program_table' => $this->programTable($election),
            'faculty_table' => $this->facultyTable($election),
            'election_progress' => $this->electionProgress($election),

            'updated_at' => now()->format('H:i:s'),
        ]);
    }

    private function authorizeOfficer(Election $election): void
    {
        $officer = auth('stuofficer')->user();

        $isOfficer = $election->generalOfficers()
            ->where('students.id', $officer->id)
            ->wherePivot('is_active', 1)
            ->exists();

        abort_if(!$isOfficer, 403, 'Not assigned for this election.');
    }

    private function programTable(Election $election)
    {
        return DB::table('programs as p')
            ->leftJoin('students as s', function ($join) {
                $join->on('s.program_id', '=', 'p.id')
                    ->where('s.status', '=', 'Active');
            })
            ->leftJoin('election_votes as v', function ($join) use ($election) {
                $join->on('v.student_id', '=', 's.id')
                    ->where('v.election_id', '=', $election->id);
            })
            ->selectRaw("
                p.id,
                p.name,
                COUNT(DISTINCT s.id) as eligible,
                COUNT(DISTINCT v.student_id) as voted
            ")
            ->groupBy('p.id', 'p.name')
            ->get()
            ->map(function ($row) {
                $eligible = (int) $row->eligible;
                $voted = (int) $row->voted;
                $remaining = max($eligible - $voted, 0);

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'eligible' => $eligible,
                    'voted' => $voted,
                    'remaining' => $remaining,
                    'percent' => $eligible > 0
                        ? round(($voted / $eligible) * 100, 2)
                        : 0,
                ];
            })
            ->sortByDesc('percent')
            ->values();
    }

    private function facultyTable(Election $election)
    {
        return DB::table('faculties as f')
            ->leftJoin('students as s', function ($join) {
                $join->on('s.faculty_id', '=', 'f.id')
                    ->where('s.status', '=', 'Active');
            })
            ->leftJoin('election_votes as v', function ($join) use ($election) {
                $join->on('v.student_id', '=', 's.id')
                    ->where('v.election_id', '=', $election->id);
            })
            ->selectRaw("
                f.id,
                f.name,
                COUNT(DISTINCT s.id) as eligible,
                COUNT(DISTINCT v.student_id) as voted
            ")
            ->groupBy('f.id', 'f.name')
            ->get()
            ->map(function ($row) {
                $eligible = (int) $row->eligible;
                $voted = (int) $row->voted;
                $remaining = max($eligible - $voted, 0);

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'eligible' => $eligible,
                    'voted' => $voted,
                    'remaining' => $remaining,
                    'percent' => $eligible > 0
                        ? round(($voted / $eligible) * 100, 2)
                        : 0,
                ];
            })
            ->sortByDesc('percent')
            ->values();
    }

    private function electionProgress(Election $election): array
    {
        $positions = ElectionPosition::where('election_id', $election->id)
            ->where('is_enabled', true)
            ->withCount('candidates')
            ->get();

        return [
            'status' => strtoupper($election->status),
            'positions' => $positions->count(),
            'candidates' => $positions->sum('candidates_count'),
            'global_positions' => $positions->where('scope_type', 'global')->count(),
            'program_positions' => $positions->where('scope_type', 'program')->count(),
            'faculty_positions' => $positions->where('scope_type', 'faculty')->count(),
        ];
    }

    private function timeRemainingPercent(Election $election): float
    {
        if (!$election->start_date || !$election->end_date || !$election->open_time || !$election->close_time) {
            return 0;
        }

        $start = Carbon::parse($election->start_date->format('Y-m-d') . ' ' . $election->open_time);
        $end = Carbon::parse($election->end_date->format('Y-m-d') . ' ' . $election->close_time);
        $now = now();

        if ($now->gte($end)) {
            return 0;
        }

        if ($now->lte($start)) {
            return 100;
        }

        $total = $start->diffInSeconds($end);
        $remaining = $now->diffInSeconds($end);

        return $total > 0 ? round(($remaining / $total) * 100, 2) : 0;
    }
}