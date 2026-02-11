<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionVote;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class OfficerResultController extends Controller
{
    // ✅ adjust if your pivot tables are different
    private string $posFacultyPivot = 'election_position_faculty';
    private string $posProgramPivot = 'election_position_program';

    public function index()
    {
        $elections = Election::query()
            ->whereIn('status', ['closed', 'published'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('officer.results.index', compact('elections'));
    }

    public function show(Election $election)
    {
        $facultyPivot = $this->posFacultyPivot;
        $programPivot = $this->posProgramPivot;

        // ------------------------------------------------------------
        // 0) Base counts (overall turnout = voters across election / all active students)
        // ------------------------------------------------------------
        $totalActiveStudents = Student::where('status', 'Active')->count();

        $votersCount = ElectionVote::where('election_id', $election->id)
            ->distinct('student_id')
            ->count('student_id');

        $overallTurnoutPercent = $totalActiveStudents > 0
            ? round(($votersCount / $totalActiveStudents) * 100, 2)
            : 0;

        // ------------------------------------------------------------
        // 1) Load positions (priority: global -> program -> faculty)
        // ------------------------------------------------------------
        $positions = DB::table('election_positions as ep')
            ->join('position_definitions as pd', 'pd.id', '=', 'ep.position_definition_id')
            ->where('ep.election_id', $election->id)
            ->select([
                'ep.id',
                'ep.scope_type',
                'ep.is_enabled',
                'pd.name as position_name',
                'pd.description as position_description',
            ])
            ->orderByRaw("FIELD(ep.scope_type, 'global','program','faculty')")
            ->orderBy('ep.id')
            ->get();

        $positionIds = $positions->pluck('id')->toArray();

        // ------------------------------------------------------------
        // 2) Eligible students per position (Active only) based on scope + pivot targets
        // ------------------------------------------------------------
        $eligibleByPosition = DB::table('election_positions as ep')
            ->where('ep.election_id', $election->id)
            ->select('ep.id', 'ep.scope_type')
            ->selectRaw("
                CASE
                    WHEN ep.scope_type = 'global' THEN (
                        SELECT COUNT(*) FROM students s
                        WHERE s.status = 'Active'
                    )
                    WHEN ep.scope_type = 'faculty' THEN (
                        SELECT COUNT(*) FROM students s
                        WHERE s.status = 'Active'
                        AND s.faculty_id IN (
                            SELECT faculty_id FROM {$facultyPivot} p
                            WHERE p.election_position_id = ep.id
                        )
                    )
                    WHEN ep.scope_type = 'program' THEN (
                        SELECT COUNT(*) FROM students s
                        WHERE s.status = 'Active'
                        AND s.program_id IN (
                            SELECT program_id FROM {$programPivot} p
                            WHERE p.election_position_id = ep.id
                        )
                    )
                    ELSE 0
                END AS eligible_students
            ")
            ->get()
            ->keyBy('id');

        // ------------------------------------------------------------
        // 3A) OVERALL votes per candidate (INCLUDES candidates with 0 votes)
        // - votes counted ONLY from eligible voters for that scope
        // - percent = vote_count / eligible_students
        // ------------------------------------------------------------
        $overallCandidatesAll = DB::table('election_positions as ep')
            ->join('position_definitions as pd', 'pd.id', '=', 'ep.position_definition_id')
            ->join('election_candidates as ec', 'ec.election_position_id', '=', 'ep.id')
            ->join('students as cs', 'cs.id', '=', 'ec.student_id') // candidate student
            ->leftJoin('election_votes as v', function ($join) use ($election) {
                $join->on('v.election_position_id', '=', 'ep.id')
                    ->on('v.candidate_id', '=', 'ec.id')
                    ->where('v.election_id', '=', $election->id);
            })
            ->leftJoin('students as voter', function ($join) {
                $join->on('voter.id', '=', 'v.student_id')
                    ->where('voter.status', '=', 'Active');
            })
            ->where('ep.election_id', $election->id)
            ->whereIn('ep.id', $positionIds)
            ->selectRaw("
                ep.id as position_id,
                ep.scope_type,
                pd.name as position_name,

                ec.id as candidate_id,
                CONCAT(cs.first_name, ' ', COALESCE(cs.last_name,'')) as candidate_name,
                cs.reg_no as candidate_reg_no,

                SUM(
                    CASE
                        WHEN voter.id IS NULL THEN 0
                        WHEN ep.scope_type = 'global' THEN 1
                        WHEN ep.scope_type = 'faculty' AND voter.faculty_id IN (
                            SELECT pfp.faculty_id FROM {$facultyPivot} pfp
                            WHERE pfp.election_position_id = ep.id
                        ) THEN 1
                        WHEN ep.scope_type = 'program' AND voter.program_id IN (
                            SELECT ppp.program_id FROM {$programPivot} ppp
                            WHERE ppp.election_position_id = ep.id
                        ) THEN 1
                        ELSE 0
                    END
                ) as vote_count
            ")
            ->groupBy(
                'ep.id',
                'ep.scope_type',
                'pd.name',
                'ec.id',
                'cs.first_name',
                'cs.last_name',
                'cs.reg_no'
            )
            ->orderByRaw("FIELD(ep.scope_type, 'global','program','faculty')")
            ->orderBy('ep.id')
            ->orderByDesc('vote_count')
            ->get()
            ->groupBy('position_id');

        // ------------------------------------------------------------
        // 3B) Distinct voters per position (ONLY eligible voters for that scope)
        // - used for turnout per position
        // ------------------------------------------------------------
        $votersPerPosition = DB::table('election_votes as v')
            ->join('election_positions as ep', 'ep.id', '=', 'v.election_position_id')
            ->join('students as voter', 'voter.id', '=', 'v.student_id')
            ->where('v.election_id', $election->id)
            ->whereIn('v.election_position_id', $positionIds)
            ->where('voter.status', 'Active')
            ->where(function ($w) use ($facultyPivot, $programPivot) {
                $w->where('ep.scope_type', 'global')

                    ->orWhere(function ($q) use ($facultyPivot) {
                        $q->where('ep.scope_type', 'faculty')
                            ->whereIn('voter.faculty_id', function ($sub) use ($facultyPivot) {
                                $sub->select('pfp.faculty_id')
                                    ->from($facultyPivot . ' as pfp')
                                    ->whereColumn('pfp.election_position_id', 'ep.id');
                            });
                    })

                    ->orWhere(function ($q) use ($programPivot) {
                        $q->where('ep.scope_type', 'program')
                            ->whereIn('voter.program_id', function ($sub) use ($programPivot) {
                                $sub->select('ppp.program_id')
                                    ->from($programPivot . ' as ppp')
                                    ->whereColumn('ppp.election_position_id', 'ep.id');
                            });
                    });
            })
            ->selectRaw("
                v.election_position_id as position_id,
                COUNT(DISTINCT v.student_id) as voters
            ")
            ->groupBy('v.election_position_id')
            ->get()
            ->keyBy('position_id');

        // ------------------------------------------------------------
        // 3C) Final overall per-position results (ALWAYS includes candidates, even with 0 votes)
        // ------------------------------------------------------------
        $finalOverallResults = collect();

        foreach ($positions as $pos) {
            $posId = (int) $pos->id;

            $rows = $overallCandidatesAll->get($posId, collect());
            $eligible = (int) ($eligibleByPosition[$posId]->eligible_students ?? 0);
            $voters   = (int) ($votersPerPosition[$posId]->voters ?? 0);
            $turnoutPercent = $eligible > 0 ? round(($voters / $eligible) * 100, 2) : 0;

            $ranked = $rows
                ->map(function ($r) use ($eligible) {
                    $r->vote_percent = $eligible > 0 ? round(((int)$r->vote_count / $eligible) * 100, 2) : 0; // ✅ percent of eligible
                    return $r;
                })
                ->sortByDesc('vote_count')
                ->values();

            // ranks (ties share rank)
            $rank = 1;
            foreach ($ranked as $i => $r) {
                if ($i > 0 && (int)$r->vote_count < (int)$ranked[$i - 1]->vote_count) {
                    $rank = $i + 1;
                }
                $r->rank = $rank;
            }

            $finalOverallResults->put($posId, [
                'position_id'        => $posId,
                'position_name'      => $pos->position_name ?? ($ranked->first()->position_name ?? 'Unknown'),
                'scope_type'         => $pos->scope_type ?? ($ranked->first()->scope_type ?? 'global'),

                // ✅ eligible + voters + turnout for THIS position scope
                'eligible_students'  => $eligible,
                'voters'             => $voters,
                'turnout_percent'    => $turnoutPercent,

                // useful for display
                'total_votes'        => (int) $ranked->sum('vote_count'),
                'candidates'         => $ranked,
            ]);
        }

        // ------------------------------------------------------------
        // 4) SUPPORT ORIGIN (where votes came from) per candidate
        // (kept)
        // ------------------------------------------------------------
        $supportByFaculty = DB::table('election_votes as v')
            ->join('students as voter', 'voter.id', '=', 'v.student_id')
            ->join('faculties as f', 'f.id', '=', 'voter.faculty_id')
            ->where('v.election_id', $election->id)
            ->selectRaw('
                v.election_position_id as position_id,
                v.candidate_id,
                f.id as faculty_id,
                f.name as faculty_name,
                COUNT(*) as votes_from_group
            ')
            ->groupBy('position_id', 'candidate_id', 'faculty_id', 'faculty_name')
            ->orderBy('position_id')
            ->orderBy('candidate_id')
            ->orderByDesc('votes_from_group')
            ->get()
            ->groupBy('position_id');

        $supportByProgram = DB::table('election_votes as v')
            ->join('students as voter', 'voter.id', '=', 'v.student_id')
            ->join('programs as p', 'p.id', '=', 'voter.program_id')
            ->where('v.election_id', $election->id)
            ->selectRaw('
                v.election_position_id as position_id,
                v.candidate_id,
                p.id as program_id,
                p.name as program_name,
                COUNT(*) as votes_from_group
            ')
            ->groupBy('position_id', 'candidate_id', 'program_id', 'program_name')
            ->orderBy('position_id')
            ->orderBy('candidate_id')
            ->orderByDesc('votes_from_group')
            ->get()
            ->groupBy('position_id');

        // ------------------------------------------------------------
        // 5) ✅ RESULTS PER FACULTY / PER PROGRAM for ALL scopes (global included),
        // AND includes candidates with 0 votes in that group.
        //
        // Meaning:
        // - "Faculty X" shows votes FROM voters in Faculty X.
        // - "Program Y" shows votes FROM voters in Program Y.
        //
        // Eligible groups per position:
        // - global: all faculties/programs that have active students
        // - faculty: only faculties attached to that position (pivot)
        // - program: only programs attached to that position (pivot)
        // ------------------------------------------------------------

        // ---- 5A) Eligible faculty groups per position (for grouping voters) ----
        $eligibleFacultyGroups = DB::query()
            ->fromSub(function ($q) use ($election, $facultyPivot) {
                // global: all faculties with active students
                $q->selectRaw("ep.id as position_id, f.id as faculty_id, f.name as faculty_name")
                    ->from('election_positions as ep')
                    ->join('faculties as f', 'f.id', '=', DB::raw('f.id'))
                    ->where('ep.election_id', $election->id)
                    ->where('ep.scope_type', 'global')
                    ->whereExists(function ($x) {
                        $x->selectRaw('1')
                            ->from('students as s')
                            ->whereColumn('s.faculty_id', 'f.id')
                            ->where('s.status', 'Active');
                    })

                    ->unionAll(
                        // faculty scope: only attached faculties
                        DB::table('election_positions as ep2')
                            ->join($facultyPivot . ' as pfp', 'pfp.election_position_id', '=', 'ep2.id')
                            ->join('faculties as f2', 'f2.id', '=', 'pfp.faculty_id')
                            ->where('ep2.election_id', $election->id)
                            ->where('ep2.scope_type', 'faculty')
                            ->selectRaw("ep2.id as position_id, f2.id as faculty_id, f2.name as faculty_name")
                    );
            }, 'ef')
            ->whereIn('ef.position_id', $positionIds)
            ->select('ef.position_id', 'ef.faculty_id', 'ef.faculty_name')
            ->distinct();

        // ---- 5B) Results per faculty (all candidates x eligible faculty groups, count votes from that faculty) ----
        $resultsPerFacultyAll = DB::query()
            ->fromSub($eligibleFacultyGroups, 'ef')
            ->join('election_positions as ep', 'ep.id', '=', 'ef.position_id')
            ->join('election_candidates as ec', 'ec.election_position_id', '=', 'ep.id')
            ->join('students as cs', 'cs.id', '=', 'ec.student_id')
            ->leftJoin('election_votes as v', function ($join) use ($election) {
                $join->on('v.election_position_id', '=', 'ep.id')
                    ->on('v.candidate_id', '=', 'ec.id')
                    ->where('v.election_id', '=', $election->id);
            })
            ->leftJoin('students as voter', function ($join) {
                $join->on('voter.id', '=', 'v.student_id')
                    ->where('voter.status', '=', 'Active');
            })
            ->selectRaw("
                ep.id as position_id,
                ep.scope_type,
                ef.faculty_id,
                ef.faculty_name,

                ec.id as candidate_id,
                CONCAT(cs.first_name, ' ', COALESCE(cs.last_name,'')) as candidate_name,
                cs.reg_no as candidate_reg_no,

                SUM(CASE WHEN voter.id IS NOT NULL AND voter.faculty_id = ef.faculty_id THEN 1 ELSE 0 END) as vote_count
            ")
            ->groupBy(
                'ep.id',
                'ep.scope_type',
                'ef.faculty_id',
                'ef.faculty_name',
                'ec.id',
                'cs.first_name',
                'cs.last_name',
                'cs.reg_no'
            )
            ->orderBy('ep.id')
            ->orderBy('ef.faculty_name')
            ->orderByDesc('vote_count')
            ->get()
            ->groupBy(['position_id', 'faculty_id']);

        // ---- 5C) Eligible program groups per position (for grouping voters) ----
        $eligibleProgramGroups = DB::query()
            ->fromSub(function ($q) use ($election, $programPivot) {
                // global: all programs with active students
                $q->selectRaw("ep.id as position_id, p.id as program_id, p.name as program_name")
                    ->from('election_positions as ep')
                    ->join('programs as p', 'p.id', '=', DB::raw('p.id'))
                    ->where('ep.election_id', $election->id)
                    ->where('ep.scope_type', 'global')
                    ->whereExists(function ($x) {
                        $x->selectRaw('1')
                            ->from('students as s')
                            ->whereColumn('s.program_id', 'p.id')
                            ->where('s.status', 'Active');
                    })

                    ->unionAll(
                        // program scope: only attached programs
                        DB::table('election_positions as ep2')
                            ->join($programPivot . ' as ppp', 'ppp.election_position_id', '=', 'ep2.id')
                            ->join('programs as p2', 'p2.id', '=', 'ppp.program_id')
                            ->where('ep2.election_id', $election->id)
                            ->where('ep2.scope_type', 'program')
                            ->selectRaw("ep2.id as position_id, p2.id as program_id, p2.name as program_name")
                    );
            }, 'epg')
            ->whereIn('epg.position_id', $positionIds)
            ->select('epg.position_id', 'epg.program_id', 'epg.program_name')
            ->distinct();

        // ---- 5D) Results per program (all candidates x eligible program groups, count votes from that program) ----
        $resultsPerProgramAll = DB::query()
            ->fromSub($eligibleProgramGroups, 'epg')
            ->join('election_positions as ep', 'ep.id', '=', 'epg.position_id')
            ->join('election_candidates as ec', 'ec.election_position_id', '=', 'ep.id')
            ->join('students as cs', 'cs.id', '=', 'ec.student_id')
            ->leftJoin('election_votes as v', function ($join) use ($election) {
                $join->on('v.election_position_id', '=', 'ep.id')
                    ->on('v.candidate_id', '=', 'ec.id')
                    ->where('v.election_id', '=', $election->id);
            })
            ->leftJoin('students as voter', function ($join) {
                $join->on('voter.id', '=', 'v.student_id')
                    ->where('voter.status', '=', 'Active');
            })
            ->selectRaw("
                ep.id as position_id,
                ep.scope_type,
                epg.program_id,
                epg.program_name,

                ec.id as candidate_id,
                CONCAT(cs.first_name, ' ', COALESCE(cs.last_name,'')) as candidate_name,
                cs.reg_no as candidate_reg_no,

                SUM(CASE WHEN voter.id IS NOT NULL AND voter.program_id = epg.program_id THEN 1 ELSE 0 END) as vote_count
            ")
            ->groupBy(
                'ep.id',
                'ep.scope_type',
                'epg.program_id',
                'epg.program_name',
                'ec.id',
                'cs.first_name',
                'cs.last_name',
                'cs.reg_no'
            )
            ->orderBy('ep.id')
            ->orderBy('epg.program_name')
            ->orderByDesc('vote_count')
            ->get()
            ->groupBy(['position_id', 'program_id']);

        // ------------------------------------------------------------
        // 6) OPTIONAL: Turnout per FACULTY scope position per faculty (eligible vs voters)
        // and Turnout per PROGRAM scope position per program (eligible vs voters)
        // (same as your previous, kept for scope positions)
        // ------------------------------------------------------------
        $facultyScopeTurnout = DB::table($facultyPivot . ' as pfp')
            ->join('election_positions as ep', 'ep.id', '=', 'pfp.election_position_id')
            ->join('faculties as f', 'f.id', '=', 'pfp.faculty_id')
            ->leftJoin('students as s', function ($join) {
                $join->on('s.faculty_id', '=', 'f.id')->where('s.status', 'Active');
            })
            ->leftJoin('election_votes as v', function ($join) use ($election) {
                $join->on('v.student_id', '=', 's.id')
                    ->where('v.election_id', $election->id);
            })
            ->where('ep.election_id', $election->id)
            ->where('ep.scope_type', 'faculty')
            ->selectRaw("
                ep.id as position_id,
                f.id as faculty_id,
                f.name as faculty_name,
                COUNT(DISTINCT s.id) as eligible_students,
                COUNT(DISTINCT CASE WHEN v.election_position_id = ep.id THEN v.student_id END) as voters
            ")
            ->groupBy('ep.id', 'f.id', 'f.name')
            ->get()
            ->groupBy('position_id');

        $programScopeTurnout = DB::table($programPivot . ' as ppp')
            ->join('election_positions as ep', 'ep.id', '=', 'ppp.election_position_id')
            ->join('programs as p', 'p.id', '=', 'ppp.program_id')
            ->leftJoin('students as s', function ($join) {
                $join->on('s.program_id', '=', 'p.id')->where('s.status', 'Active');
            })
            ->leftJoin('election_votes as v', function ($join) use ($election) {
                $join->on('v.student_id', '=', 's.id')
                    ->where('v.election_id', $election->id);
            })
            ->where('ep.election_id', $election->id)
            ->where('ep.scope_type', 'program')
            ->selectRaw("
                ep.id as position_id,
                p.id as program_id,
                p.name as program_name,
                COUNT(DISTINCT s.id) as eligible_students,
                COUNT(DISTINCT CASE WHEN v.election_position_id = ep.id THEN v.student_id END) as voters
            ")
            ->groupBy('ep.id', 'p.id', 'p.name')
            ->get()
            ->groupBy('position_id');

        // ------------------------------------------------------------
        // 7) Pack for view
        // ------------------------------------------------------------
        $resultsPerPosition = $positions->map(function ($pos) use (
            $finalOverallResults,
            $supportByFaculty,
            $supportByProgram,
            $eligibleByPosition,
            $resultsPerFacultyAll,
            $resultsPerProgramAll,
            $facultyScopeTurnout,
            $programScopeTurnout
        ) {
            $overall = $finalOverallResults->get($pos->id);

            return (object) [
                'position_id'   => $pos->id,
                'position_name' => $pos->position_name,
                'scope_type'    => $pos->scope_type,

                // ✅ overall results: includes 0-vote candidates + eligible + turnout
                'overall'       => $overall,

                // ✅ eligible count per position even if 0 votes
                'eligible_students' => (int) ($eligibleByPosition[$pos->id]->eligible_students ?? 0),

                // trends
                'support_faculty' => $supportByFaculty->get($pos->id, collect()),
                'support_program' => $supportByProgram->get($pos->id, collect()),

                // ✅ NEW: for ALL scopes (global included) show results per faculty/program (with 0 votes)
                'by_faculty_all' => $resultsPerFacultyAll->get($pos->id, collect()), // groupBy faculty_id => rows
                'by_program_all' => $resultsPerProgramAll->get($pos->id, collect()), // groupBy program_id => rows

                // turnout for scope positions (optional)
                'turnout_by_faculty' => $facultyScopeTurnout->get($pos->id, collect()),
                'turnout_by_program' => $programScopeTurnout->get($pos->id, collect()),
            ];
        });

        return view('officer.results.show', compact(
            'election',
            'totalActiveStudents',
            'votersCount',
            'overallTurnoutPercent',
            'resultsPerPosition'
        ));
    }


public function voters(Election $election, Request $request)
{
    $q         = trim((string) $request->get('q', ''));
    $facultyId = $request->integer('faculty_id');
    $programId = $request->integer('program_id');

    $rows = DB::table('election_votes as v')
        ->join('students as s', 's.id', '=', 'v.student_id')
        ->leftJoin('faculties as f', 'f.id', '=', 's.faculty_id')
        ->leftJoin('programs as p', 'p.id', '=', 's.program_id')
        ->leftJoin('election_positions as ep', 'ep.id', '=', 'v.election_position_id')
        ->where('v.election_id', $election->id)
        ->when($facultyId, fn($qq) => $qq->where('s.faculty_id', $facultyId))
        ->when($programId, fn($qq) => $qq->where('s.program_id', $programId))
        ->when($q !== '', function ($qq) use ($q) {
            $qq->where(function ($w) use ($q) {
                $w->where('s.reg_no', 'like', "%{$q}%")
                  ->orWhereRaw("CONCAT(s.first_name,' ',COALESCE(s.last_name,'')) LIKE ?", ["%{$q}%"]);
            });
        })
        ->selectRaw("
            s.id as student_id,
            CONCAT(s.first_name,' ',COALESCE(s.last_name,'')) as student_name,
            s.reg_no,
            f.name as faculty_name,
            p.name as program_name,
            COUNT(*) as total_votes,
            COUNT(DISTINCT v.election_position_id) as positions_voted,
            SUM(CASE WHEN ep.scope_type = 'global'  THEN 1 ELSE 0 END) as global_votes,
            SUM(CASE WHEN ep.scope_type = 'faculty' THEN 1 ELSE 0 END) as faculty_votes,
            SUM(CASE WHEN ep.scope_type = 'program' THEN 1 ELSE 0 END) as program_votes,
            COUNT(DISTINCT ep.scope_type) as categories_participated
        ")
        ->groupBy('s.id', 's.first_name', 's.last_name', 's.reg_no', 'f.name', 'p.name')
        ->orderByDesc('total_votes')
        ->orderBy('student_name')
        ->paginate(30)
        ->withQueryString();

    $totalVoters = DB::table('election_votes')
        ->where('election_id', $election->id)
        ->distinct('student_id')
        ->count('student_id');

    $totalVotes = DB::table('election_votes')
        ->where('election_id', $election->id)
        ->count();

    // ✅ add these
    $faculties = DB::table('faculties')->select('id','name')->orderBy('name')->get();
    $programs  = DB::table('programs')->select('id','name')->orderBy('name')->get();

    // ✅ return the correct blade path you are using
    return view('elections.voters', compact(
        'election', 'rows', 'totalVoters', 'totalVotes', 'faculties', 'programs'
    ));
}


public function votersPdf(Election $election, Request $request)
{
    $q         = trim((string) $request->get('q', ''));
    $facultyId = $request->integer('faculty_id');
    $programId = $request->integer('program_id');
    $scope     = $request->get('export_scope', 'all'); // all|faculty|program

    // enforce selection if user chose a specific scope
    if ($scope === 'faculty' && !$facultyId) {
        return back()->with('error', 'Please select a faculty to export by faculty.');
    }
    if ($scope === 'program' && !$programId) {
        return back()->with('error', 'Please select a program to export by program.');
    }

    $rows = DB::table('election_votes as v')
        ->join('students as s', 's.id', '=', 'v.student_id')
        ->leftJoin('faculties as f', 'f.id', '=', 's.faculty_id')
        ->leftJoin('programs as p', 'p.id', '=', 's.program_id')
        ->leftJoin('election_positions as ep', 'ep.id', '=', 'v.election_position_id')
        ->where('v.election_id', $election->id)
        ->when($facultyId, fn($qq) => $qq->where('s.faculty_id', $facultyId))
        ->when($programId, fn($qq) => $qq->where('s.program_id', $programId))
        ->when($q !== '', function ($qq) use ($q) {
            $qq->where(function ($w) use ($q) {
                $w->where('s.reg_no', 'like', "%{$q}%")
                  ->orWhereRaw("CONCAT(s.first_name,' ',COALESCE(s.last_name,'')) LIKE ?", ["%{$q}%"]);
            });
        })
        ->selectRaw("
            s.id as student_id,
            CONCAT(s.first_name,' ',COALESCE(s.last_name,'')) as student_name,
            s.reg_no,
            f.name as faculty_name,
            p.name as program_name,
            COUNT(*) as total_votes,
            SUM(CASE WHEN ep.scope_type = 'global'  THEN 1 ELSE 0 END) as global_votes,
            SUM(CASE WHEN ep.scope_type = 'faculty' THEN 1 ELSE 0 END) as faculty_votes,
            SUM(CASE WHEN ep.scope_type = 'program' THEN 1 ELSE 0 END) as program_votes,
            COUNT(DISTINCT ep.scope_type) as categories_participated
        ")
        ->groupBy('s.id','s.first_name','s.last_name','s.reg_no','f.name','p.name')
        ->orderBy('student_name')
        ->get();

    $title = match($scope){
        'faculty' => 'Voters List (By Faculty)',
        'program' => 'Voters List (By Program)',
        default   => 'Voters List (All)',
    };

    $pdf = Pdf::loadView('elections.voters_pdf', [
        'election'   => $election,
        'rows'       => $rows,
        'title'      => $title,
        'scope'      => $scope,
        'facultyId'  => $facultyId,
        'programId'  => $programId,
        'q'          => $q,
        'generatedAt'=> now(),
    ])->setPaper('a4', 'portrait');

    $filename = 'voters_' . str($election->title)->slug('_') . '_' . now()->format('Ymd_His') . '.pdf';
    return $pdf->download($filename);
}

}
