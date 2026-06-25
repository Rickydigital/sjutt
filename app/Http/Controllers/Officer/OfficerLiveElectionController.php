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
    private string $facultyPivot = 'election_position_faculty';
    private string $programPivot = 'election_position_program';

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
        $officer = auth('stuofficer')->user();

        $isOfficer = $election->generalOfficers()
            ->where('students.id', $officer->id)
            ->wherePivot('is_active', 1)
            ->exists();

        abort_if(!$isOfficer, 403, 'Not assigned for this election.');

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

            'positions' => $this->positionsLive($election),

            'faculty_turnout' => $this->facultyTurnout($election),

            'program_turnout' => $this->programTurnout($election),

            'updated_at' => now()->format('H:i:s'),
        ]);
    }

    private function positionsLive(Election $election)
    {
        $positions = ElectionPosition::query()
            ->where('election_id', $election->id)
            ->where('is_enabled', true)
            ->with(['definition', 'candidates.student.faculty', 'candidates.student.program'])
            ->orderByRaw("FIELD(scope_type, 'global','program','faculty')")
            ->orderBy('id')
            ->get();

        return $positions->map(function ($position) use ($election) {
            $totalVotes = ElectionVote::where('election_id', $election->id)
                ->where('election_position_id', $position->id)
                ->count();

            $uniqueVoters = ElectionVote::where('election_id', $election->id)
                ->where('election_position_id', $position->id)
                ->distinct('student_id')
                ->count('student_id');

            $candidates = $position->candidates->map(function ($candidate) use ($election, $position, $totalVotes) {
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
                    'percent' => $totalVotes > 0 ? round(($votes / $totalVotes) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('votes')
            ->values()
            ->map(function ($candidate, $index) {
                $candidate['rank'] = $index + 1;
                return $candidate;
            });

            return [
                'id' => $position->id,
                'name' => $position->definition->name ?? 'Position',
                'scope' => $position->scope_type,
                'voters' => $uniqueVoters,
                'total_votes' => $totalVotes,
                'candidates' => $candidates,
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
                    'name' => $row->name,
                    'eligible' => $eligible,
                    'voters' => $voters,
                    'turnout' => $eligible > 0 ? round(($voters / $eligible) * 100, 2) : 0,
                ];
            });
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
                    'name' => $row->name,
                    'eligible' => $eligible,
                    'voters' => (int) $row->voters,
                    'turnout' => $eligible > 0 ? round(((int)$row->voters / $eligible) * 100, 2) : 0,
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