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

        $closeAt = null;
        if ($election->end_date && $election->close_time) {
            $closeAt = Carbon::parse($election->end_date->format('Y-m-d') . ' ' . $election->close_time);
        }

        return response()->json([
            'election' => [
                'id' => $election->id,
                'title' => $election->title,
                'status' => $election->status,
                'close_at' => $closeAt?->toIso8601String(),
            ],
            'summary' => [
                'eligible' => $eligible,
                'unique_voters' => $uniqueVoters,
                'total_votes' => $totalVotes,
                'remaining' => $remaining,
                'turnout' => $eligible > 0 ? round(($uniqueVoters / $eligible) * 100, 2) : 0,
                'remaining_percent' => $eligible > 0 ? round(($remaining / $eligible) * 100, 2) : 0,
                'time_remaining_percent' => $this->timeRemainingPercent($election),
            ],
            'program_table' => $this->programTable($election),
            'faculty_table' => $this->facultyTable($election),
            'election_progress' => $this->electionProgress($election),

            'global_positions' => $this->globalPositions($election),
            'program_positions' => $this->programPositions($election),
            'faculty_positions' => $this->facultyPositions($election),

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

    private function globalPositions(Election $election)
    {
        return ElectionPosition::query()
            ->where('election_id', $election->id)
            ->where('is_enabled', true)
            ->where('scope_type', 'global')
            ->with(['definition', 'candidates.student.faculty', 'candidates.student.program'])
            ->get()
            ->map(fn ($position) => $this->buildPosition($election, $position, $position->candidates));
    }

    private function programPositions(Election $election)
    {
        $positions = ElectionPosition::query()
            ->where('election_id', $election->id)
            ->where('is_enabled', true)
            ->where('scope_type', 'program')
            ->with(['definition', 'candidates.student.faculty', 'candidates.student.program'])
            ->get();

        $groups = [];

        foreach ($positions as $position) {
            foreach ($position->candidates->groupBy('program_id') as $programId => $candidates) {
                $name = $candidates->first()?->student?->program?->name ?? 'Unknown Program';

                if (!isset($groups[$programId])) {
                    $groups[$programId] = [
                        'id' => $programId,
                        'name' => $name,
                        'positions' => [],
                    ];
                }

                $groups[$programId]['positions'][] = $this->buildPosition($election, $position, $candidates);
            }
        }

        return array_values($groups);
    }

    private function facultyPositions(Election $election)
    {
        $positions = ElectionPosition::query()
            ->where('election_id', $election->id)
            ->where('is_enabled', true)
            ->where('scope_type', 'faculty')
            ->with(['definition', 'candidates.student.faculty', 'candidates.student.program'])
            ->get();

        $groups = [];

        foreach ($positions as $position) {
            foreach ($position->candidates->groupBy('faculty_id') as $facultyId => $candidates) {
                $name = $candidates->first()?->student?->faculty?->name ?? 'Unknown Faculty';

                if (!isset($groups[$facultyId])) {
                    $groups[$facultyId] = [
                        'id' => $facultyId,
                        'name' => $name,
                        'positions' => [],
                    ];
                }

                $groups[$facultyId]['positions'][] = $this->buildPosition($election, $position, $candidates);
            }
        }

        return array_values($groups);
    }

    private function buildPosition(Election $election, ElectionPosition $position, $candidates)
    {
        $rows = $candidates->map(function ($candidate) use ($election, $position) {
            $votes = ElectionVote::where('election_id', $election->id)
                ->where('election_position_id', $position->id)
                ->where('candidate_id', $candidate->id)
                ->count();

            return [
                'id' => $candidate->id,
                'name' => trim(($candidate->student->first_name ?? '') . ' ' . ($candidate->student->last_name ?? '')),
                'reg_no' => $candidate->student->reg_no ?? null,
                'faculty' => $candidate->student->faculty->name ?? null,
                'program' => $candidate->student->program->name ?? null,
                'votes' => $votes,
            ];
        });

        $total = $rows->sum('votes');

        $rows = $rows->sortByDesc('votes')->values()->map(function ($row, $index) use ($total) {
            $row['rank'] = $index + 1;
            $row['percent'] = $total > 0 ? round(($row['votes'] / $total) * 100, 2) : 0;
            return $row;
        });

        return [
            'id' => $position->id,
            'name' => $position->definition->name ?? 'Position',
            'scope' => $position->scope_type,
            'total_votes' => $total,
            'candidates' => $rows,
        ];
    }

    private function programTable(Election $election)
    {
        return DB::table('programs as p')
            ->leftJoin('students as s', fn ($j) => $j->on('s.program_id', '=', 'p.id')->where('s.status', 'Active'))
            ->leftJoin('election_votes as v', fn ($j) => $j->on('v.student_id', '=', 's.id')->where('v.election_id', $election->id))
            ->selectRaw('p.id, p.name, COUNT(DISTINCT s.id) as eligible, COUNT(DISTINCT v.student_id) as voted')
            ->groupBy('p.id', 'p.name')
            ->get()
            ->map(fn ($r) => $this->rowFormat($r))
            ->sortByDesc('percent')
            ->values();
    }

    private function facultyTable(Election $election)
    {
        return DB::table('faculties as f')
            ->leftJoin('students as s', fn ($j) => $j->on('s.faculty_id', '=', 'f.id')->where('s.status', 'Active'))
            ->leftJoin('election_votes as v', fn ($j) => $j->on('v.student_id', '=', 's.id')->where('v.election_id', $election->id))
            ->selectRaw('f.id, f.name, COUNT(DISTINCT s.id) as eligible, COUNT(DISTINCT v.student_id) as voted')
            ->groupBy('f.id', 'f.name')
            ->get()
            ->map(fn ($r) => $this->rowFormat($r))
            ->sortByDesc('percent')
            ->values();
    }

    private function rowFormat($row): array
    {
        $eligible = (int) $row->eligible;
        $voted = (int) $row->voted;

        return [
            'id' => $row->id,
            'name' => $row->name,
            'eligible' => $eligible,
            'voted' => $voted,
            'remaining' => max($eligible - $voted, 0),
            'percent' => $eligible > 0 ? round(($voted / $eligible) * 100, 2) : 0,
        ];
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

        if (now()->gte($end)) return 0;
        if (now()->lte($start)) return 100;

        $total = $start->diffInSeconds($end);
        $remaining = now()->diffInSeconds($end);

        return $total > 0 ? round(($remaining / $total) * 100, 2) : 0;
    }
}