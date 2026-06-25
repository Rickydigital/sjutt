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
        $officer = auth('stuofficer')->user();

        $isOfficer = $election->generalOfficers()
            ->where('students.id', $officer->id)
            ->wherePivot('is_active', 1)
            ->exists();

        abort_if(!$isOfficer, 403, 'Not assigned for this election.');

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

        $turnout = $eligible > 0 ? round(($uniqueVoters / $eligible) * 100, 2) : 0;

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
                'not_voted' => max($eligible - $uniqueVoters, 0),
                'turnout' => $turnout,
            ],

            'scope_summary' => $this->scopeSummary($election),

            'global_positions' => $this->globalPositions($election),

            'program_positions' => $this->programPositions($election),

            'faculty_positions' => $this->facultyPositions($election),

            'program_turnout' => $this->programTurnout($election),

            'faculty_turnout' => $this->facultyTurnout($election),

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
            ->orderBy('id')
            ->get()
            ->map(fn ($position) => $this->buildPositionResult($election, $position, 'global'));
    }

    private function programPositions(Election $election)
    {
        $positions = ElectionPosition::query()
            ->where('election_id', $election->id)
            ->where('is_enabled', true)
            ->where('scope_type', 'program')
            ->with(['definition', 'candidates.student.faculty', 'candidates.student.program'])
            ->orderBy('id')
            ->get();

        $programGroups = [];

        foreach ($positions as $position) {
            $groupedCandidates = $position->candidates->groupBy('program_id');

            foreach ($groupedCandidates as $programId => $candidates) {
                $programName = $candidates->first()?->student?->program?->name ?? 'Unknown Program';

                if (!isset($programGroups[$programId])) {
                    $programGroups[$programId] = [
                        'id' => $programId,
                        'name' => $programName,
                        'positions' => [],
                    ];
                }

                $programGroups[$programId]['positions'][] = $this->buildPositionResult(
                    $election,
                    $position,
                    'program',
                    $candidates
                );
            }
        }

        return array_values($programGroups);
    }

    private function facultyPositions(Election $election)
    {
        $positions = ElectionPosition::query()
            ->where('election_id', $election->id)
            ->where('is_enabled', true)
            ->where('scope_type', 'faculty')
            ->with(['definition', 'candidates.student.faculty', 'candidates.student.program'])
            ->orderBy('id')
            ->get();

        $facultyGroups = [];

        foreach ($positions as $position) {
            $groupedCandidates = $position->candidates->groupBy('faculty_id');

            foreach ($groupedCandidates as $facultyId => $candidates) {
                $facultyName = $candidates->first()?->student?->faculty?->name ?? 'Unknown Faculty';

                if (!isset($facultyGroups[$facultyId])) {
                    $facultyGroups[$facultyId] = [
                        'id' => $facultyId,
                        'name' => $facultyName,
                        'positions' => [],
                    ];
                }

                $facultyGroups[$facultyId]['positions'][] = $this->buildPositionResult(
                    $election,
                    $position,
                    'faculty',
                    $candidates
                );
            }
        }

        return array_values($facultyGroups);
    }

    private function buildPositionResult(Election $election, ElectionPosition $position, string $scope, $candidateCollection = null)
    {
        $candidates = $candidateCollection ?? $position->candidates;

        $candidateRows = $candidates->map(function ($candidate) use ($election, $position) {
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

        $totalVotes = $candidateRows->sum('votes');

        $candidateRows = $candidateRows
            ->sortByDesc('votes')
            ->values()
            ->map(function ($candidate, $index) use ($totalVotes) {
                $candidate['rank'] = $index + 1;
                $candidate['percent'] = $totalVotes > 0
                    ? round(($candidate['votes'] / $totalVotes) * 100, 2)
                    : 0;

                return $candidate;
            });

        return [
            'id' => $position->id,
            'name' => $position->definition->name ?? 'Position',
            'scope' => $scope,
            'total_votes' => $totalVotes,
            'candidates' => $candidateRows,
        ];
    }

    private function programTurnout(Election $election)
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
                COUNT(DISTINCT v.student_id) as voters
            ")
            ->groupBy('p.id', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->map(function ($row) {
                $eligible = (int) $row->eligible;
                $voters = (int) $row->voters;

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'eligible' => $eligible,
                    'voters' => $voters,
                    'turnout' => $eligible > 0 ? round(($voters / $eligible) * 100, 2) : 0,
                ];
            });
    }

    private function facultyTurnout(Election $election)
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
                COUNT(DISTINCT v.student_id) as voters
            ")
            ->groupBy('f.id', 'f.name')
            ->orderBy('f.name')
            ->get()
            ->map(function ($row) {
                $eligible = (int) $row->eligible;
                $voters = (int) $row->voters;

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'eligible' => $eligible,
                    'voters' => $voters,
                    'turnout' => $eligible > 0 ? round(($voters / $eligible) * 100, 2) : 0,
                ];
            });
    }

    private function scopeSummary(Election $election)
    {
        $positions = ElectionPosition::where('election_id', $election->id)
            ->where('is_enabled', true)
            ->select('scope_type', DB::raw('COUNT(*) as total'))
            ->groupBy('scope_type')
            ->pluck('total', 'scope_type');

        return [
            'global' => (int) ($positions['global'] ?? 0),
            'faculty' => (int) ($positions['faculty'] ?? 0),
            'program' => (int) ($positions['program'] ?? 0),
        ];
    }
}