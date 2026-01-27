<?php

namespace App\Http\Controllers;

use App\Models\ExaminationTimetable;
use App\Models\ExamSetup;
use App\Models\Faculty;
use App\Models\Venue;
use App\Models\Course;
use App\Models\Program;
use App\Models\FacultyGroup;
use App\Models\Semester;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ExaminationTimetableController extends Controller
{
    /**
     * IMPORTANT NOTE (fix your earlier 500 error):
     * If your pivot table `examination_timetable_lecturer` DOES NOT have column `role`,
     * then in ExaminationTimetable model you MUST remove `->withPivot('role')` from lecturers() relation
     * OR add the `role` column in migration.
     */

   public function index(Request $request)
{
    $setups = ExamSetup::with(['semester', 'examinationTimetables'])
        ->orderByDesc('id')
        ->get();

    $allPrograms = Program::all();
    $venues = Venue::all();

    $academicYears = range(2010, 2025);
    $academicYears = array_map(fn ($y) => "$y/" . ($y + 1), $academicYears);
    $semesters = Semester::orderBy('name', 'asc')->get();

    // defaults
    $setup = null;
    $days = [];
    $timeSlots = [];
    $programs = Program::all();

    $timetables = collect();
    $classes = collect();
    $grid = [];
    $dateChunks = [];

    $setupId = $request->query('setup_id');
    $programId = $request->query('program_id');

    // ✅ IMPORTANT: DO NOT auto-pick first setup
    if (!empty($setupId)) {
        $setup = $setups->firstWhere('id', (int) $setupId);
    }

    if ($setup) {
        $days = $this->getValidDates($setup);
        $timeSlots = $setup->time_slots ?? [];
        $dateChunks = array_chunk($days, 5);

        // DO NOT load timetable until a program is selected
        if (!empty($programId)) {

            $classes = Faculty::where('program_id', (int) $programId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            $timetables = ExaminationTimetable::with(['course', 'venues', 'supervisors'])
                ->where('exam_setup_id', $setup->id)
                ->where('program_id', (int) $programId)
                ->whereIn('exam_date', $days)
                ->get();

            foreach ($timetables as $tt) {
                $dateKey  = Carbon::parse($tt->exam_date)->format('Y-m-d');
                $startKey = Carbon::parse($tt->start_time)->format('H:i');

                $grid[$tt->faculty_id][$dateKey][$startKey][] = $tt;
            }
        }
    }

    return view('timetables.index', compact(
        'setups',
        'setup',
        'days',
        'timeSlots',
        'programs',
        'timetables',
        'allPrograms',
        'venues',
        'academicYears',
        'semesters',
        'programId',
        'classes',
        'grid',
        'dateChunks'
    ));
}


    // ----------------------------
    // SETUP CRUD (JSON + actions)
    // ----------------------------

    public function showSetup(ExamSetup $setup)
    {
        $setup->load('semester');

        return response()->json([
            'id' => $setup->id,
            'semester' => $setup->semester?->name,
            'academic_year' => $setup->academic_year,
            'start_date' => $setup->start_date?->format('Y-m-d'),
            'end_date' => $setup->end_date?->format('Y-m-d'),
            'include_weekends' => (bool)$setup->include_weekends,
            'time_slots' => $setup->time_slots ?? [],
        ]);
    }

    public function editSetup(ExamSetup $setup)
    {
        return response()->json([
            'setup' => $setup,
            'semesters' => Semester::orderBy('name', 'asc')->get(['id', 'name']),
        ]);
    }

    public function storeSetup(Request $request)
    {
        // NOTE: your view now uses semester_id, not "semester"
        $validated = $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'academic_year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'include_weekends' => 'nullable|boolean',
            'time_slots' => 'required|array|min:1',
            'time_slots.*.name' => 'required|string|max:255',
            'time_slots.*.start_time' => 'required|date_format:H:i',
            'time_slots.*.end_time' => 'required|date_format:H:i|after:time_slots.*.start_time',
        ]);

        $validated['include_weekends'] = $request->has('include_weekends');

        ExamSetup::create($validated);

        return redirect()->route('timetables.index')->with('success', 'Setup created successfully.');
    }

    public function updateSetup(Request $request, ExamSetup $setup)
    {
        $validated = $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'academic_year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'include_weekends' => 'nullable|boolean',
            'time_slots' => 'required|array|min:1',
            'time_slots.*.name' => 'required|string|max:255',
            'time_slots.*.start_time' => 'required|date_format:H:i',
            'time_slots.*.end_time' => 'required|date_format:H:i|after:time_slots.*.start_time',
        ]);

        $validated['include_weekends'] = $request->has('include_weekends');

        $setup->update($validated);

        return redirect()->route('timetables.index')->with('success', 'Setup updated successfully.');
    }

    public function destroySetup(ExamSetup $setup)
    {
        DB::beginTransaction();
        try {
            ExaminationTimetable::where('exam_setup_id', $setup->id)->delete();
            $setup->delete();

            DB::commit();
            return back()->with('success', 'Setup deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Destroy setup failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete setup: ' . $e->getMessage()]);
        }
    }

    public function clearTimetables(ExamSetup $setup)
    {
        ExaminationTimetable::where('exam_setup_id', $setup->id)->delete();
        return back()->with('success', 'All timetables cleared for this setup.');
    }

public function exportPdf(Request $request, ExamSetup $setup)
{
    $validated = $request->validate([
        'scope' => 'required|in:all,single',
        'program_id' => 'nullable|required_if:scope,single|exists:programs,id',
        'draft' => 'required|string|max:50',
    ]);

    $draft = $validated['draft'];

    $days = $this->getValidDates($setup); // your weekend-aware function
    $dateChunks = array_chunk($days, 5);

    $timeSlots = collect($setup->time_slots ?? [])->map(function($slot){
        return [
            'name' => $slot['name'] ?? 'Session',
            'start_time' => Carbon::parse($slot['start_time'])->format('H:i'),
            'end_time' => Carbon::parse($slot['end_time'])->format('H:i'),
        ];
    })->toArray();

    if (empty($days) || empty($timeSlots)) {
        return back()->withErrors(['export' => 'Setup has no valid days or time slots.']);
    }

    $programs = ($validated['scope'] === 'single')
        ? Program::where('id', (int)$validated['program_id'])->get()
        : Program::orderBy('name')->get();

    // preload all exams for these programs
    $programIds = $programs->pluck('id')->map(fn($x)=>(int)$x)->all();

    $timetables = ExaminationTimetable::with(['venues','course','faculty','program'])
        ->where('exam_setup_id', (int)$setup->id)
        ->whereIn('program_id', $programIds)
        ->whereIn('exam_date', $days)
        ->get();

    // Build grid per program:
    // $grid[program_id][faculty_id][date][start_time] = [tt...]
    $grid = [];
    foreach ($timetables as $tt) {
        $p = (int)$tt->program_id;
        $f = (int)$tt->faculty_id;
        $dateKey = Carbon::parse($tt->exam_date)->format('Y-m-d');
        $startKey = Carbon::parse($tt->start_time)->format('H:i');
        $grid[$p][$f][$dateKey][$startKey][] = $tt;
    }

    // Classes per program (faculties)
    $classesByProgram = Faculty::whereIn('program_id', $programIds)
        ->select('id','name','program_id')
        ->orderBy('name')
        ->get()
        ->groupBy('program_id');

    // Filename
    $safeDraft = str_replace([' ', '/', '\\'], '-', $draft);
    $safeSem = str_replace([' ', '/', '\\'], '-', ($setup->semester?->name ?? 'Semester'));
    $safeYear = str_replace(['/', '\\'], '-', $setup->academic_year ?? 'Year');
    $filename = "exam_timetable_{$safeYear}_{$safeSem}_{$safeDraft}.pdf";

    $pdf = Pdf::loadView('timetables.pdf', [
        'setup' => $setup,
        'draft' => $draft,
        'programs' => $programs,
        'classesByProgram' => $classesByProgram,
        'grid' => $grid,
        'dateChunks' => $dateChunks,
        'timeSlots' => $timeSlots,
    ])->setPaper('a4', 'portrait');

    return $pdf->download($filename);
}

    // ----------------------------
    // TIMETABLE CRUD (manual)
    // ----------------------------

public function store(Request $request)
{
    $validated = $request->validate([
        'exam_setup_id' => 'required|exists:exam_setups,id',
        'faculty_id' => 'required|exists:faculties,id',
        'course_code' => 'required|exists:courses,course_code',
        'exam_date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:start_time',

        'marking_date' => 'required|date',
        'uploading_date' => 'required|date|after_or_equal:marking_date',
        'nature' => 'required|in:Theory,Practical',

        'selected_venues' => 'required|array|min:1',
        'selected_venues.*' => 'exists:venues,id',
    ]);

    $setup = ExamSetup::findOrFail((int)$validated['exam_setup_id']);

    // ensure date is in setup range (and weekends rule)
    $validDates = $this->getValidDates($setup);
    $examDate = Carbon::parse($validated['exam_date'])->format('Y-m-d');
    if (!in_array($examDate, $validDates, true)) {
        return response()->json(['errors' => ['exam_date' => 'Invalid date for this setup']], 422);
    }

    // normalize dates
    $markingDate   = Carbon::parse($validated['marking_date'])->format('Y-m-d');
    $uploadingDate = Carbon::parse($validated['uploading_date'])->format('Y-m-d');
    $nature        = $validated['nature'];

    $courseCode = $validated['course_code'];
    $startTime  = $validated['start_time'];
    $endTime    = $validated['end_time'];
    $venueIds   = array_map('intval', $validated['selected_venues']);

    $isCross = $this->isCrossCateringCourse($courseCode);

    DB::beginTransaction();
    try {
        if ($isCross) {
            // all faculties from ANY program studying this course
            $faculties = $this->getFacultiesForCourse($courseCode);
            $facultyIds = $faculties->pluck('id')->map(fn($x)=>(int)$x)->all();

            // delete old versions for this slot (so "create again" replaces globally)
            $this->deleteExistingSlotExams(
                (int)$setup->id, $courseCode, $examDate, $startTime, $endTime, $facultyIds
            );

            // distribution like generate
            $facultyStudentMap = [];
            foreach ($faculties as $f) {
                $facultyStudentMap[(int)$f->id] = (int)($f->total_students_no ?? 0);
            }

            $venueModels = Venue::whereIn('id', $venueIds)->get();
            $allocation = $this->distributeStudentsAcrossVenues($facultyStudentMap, $venueModels);

            if (empty($allocation)) {
                throw new \Exception("Not enough venue capacity for cross-catering {$courseCode}.");
            }

            // create one row per faculty
            foreach ($faculties as $f) {
                $created = ExaminationTimetable::create([
                    'exam_setup_id'   => (int)$setup->id,
                    'program_id'      => (int)$f->program_id,
                    'faculty_id'      => (int)$f->id,
                    'course_code'     => $courseCode,
                    'exam_date'       => $examDate,
                    'start_time'      => $startTime,
                    'end_time'        => $endTime,

                    // ✅ new fields
                    'marking_date'    => $markingDate,
                    'uploading_date'  => $uploadingDate,
                    'nature'          => $nature,
                ]);

                // attach venues with allocated_capacity for THIS faculty
                $attach = [];
                foreach ($venueIds as $vid) {
                    $attach[$vid] = ['allocated_capacity' => (int)($allocation[$vid][$created->faculty_id] ?? 0)];
                }
                $created->venues()->sync($attach);
            }

        } else {
            // normal course -> only this faculty
            $faculty = Faculty::findOrFail((int)$validated['faculty_id']);

            // delete any previous same slot for this faculty+course in this setup
            $this->deleteExistingSlotExams(
                (int)$setup->id, $courseCode, $examDate, $startTime, $endTime, [(int)$faculty->id]
            );

            $created = ExaminationTimetable::create([
                'exam_setup_id'   => (int)$setup->id,
                'program_id'      => (int)$faculty->program_id,
                'faculty_id'      => (int)$faculty->id,
                'course_code'     => $courseCode,
                'exam_date'       => $examDate,
                'start_time'      => $startTime,
                'end_time'        => $endTime,

                // ✅ new fields
                'marking_date'    => $markingDate,
                'uploading_date'  => $uploadingDate,
                'nature'          => $nature,
            ]);

            // for non-cross, store allocated_capacity as total_students_no
            $students = (int)($faculty->total_students_no ?? 0);
            $venueModels = Venue::whereIn('id', $venueIds)->get();

            $allocation = $this->distributeStudentsAcrossVenues([(int)$faculty->id => $students], $venueModels);
            if (empty($allocation)) {
                throw new \Exception("Not enough venue capacity for {$courseCode}.");
            }

            $attach = [];
            foreach ($venueIds as $vid) {
                $attach[$vid] = ['allocated_capacity' => (int)($allocation[$vid][(int)$faculty->id] ?? 0)];
            }
            $created->venues()->sync($attach);
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'type' => $isCross ? 'cross' : 'normal',
            'message' => $isCross
                ? "Cross-catering exam created for ALL classes that study {$courseCode}."
                : "Exam created successfully for selected class."
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 422);
    }
}


public function update(Request $request, ExaminationTimetable $timetable)
{
    $validated = $request->validate([
        'faculty_id' => 'required|exists:faculties,id',
        'course_code' => 'required|exists:courses,course_code',
        'exam_date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:start_time',

        'marking_date' => 'required|date',
        'uploading_date' => 'required|date|after_or_equal:marking_date',
        'nature' => 'required|in:Theory,Practical',

        'selected_venues' => 'required|array|min:1',
        'selected_venues.*' => 'exists:venues,id',
    ]);

    $setup = ExamSetup::findOrFail((int)$timetable->exam_setup_id);

    $examDate = Carbon::parse($validated['exam_date'])->format('Y-m-d');
    $validDates = $this->getValidDates($setup);
    if (!in_array($examDate, $validDates, true)) {
        return response()->json(['errors' => ['exam_date' => 'Invalid date for this setup']], 422);
    }

    // normalize new dates
    $markingDate   = Carbon::parse($validated['marking_date'])->format('Y-m-d');
    $uploadingDate = Carbon::parse($validated['uploading_date'])->format('Y-m-d');
    $nature        = $validated['nature'];

    $courseCode = $validated['course_code'];
    $startTime  = $validated['start_time'];
    $endTime    = $validated['end_time'];
    $venueIds   = array_map('intval', $validated['selected_venues']);
    $isCross    = $this->isCrossCateringCourse($courseCode);

    DB::beginTransaction();
    try {
        if ($isCross) {
            $faculties  = $this->getFacultiesForCourse($courseCode);
            $facultyIds = $faculties->pluck('id')->map(fn($x)=>(int)$x)->all();

            // Delete ALL old cross-catering rows in that slot (including current)
            $this->deleteExistingSlotExams(
                (int)$setup->id, $courseCode, $examDate, $startTime, $endTime, $facultyIds
            );

            $facultyStudentMap = [];
            foreach ($faculties as $f) {
                $facultyStudentMap[(int)$f->id] = (int)($f->total_students_no ?? 0);
            }

            $venueModels = Venue::whereIn('id', $venueIds)->get();
            $allocation  = $this->distributeStudentsAcrossVenues($facultyStudentMap, $venueModels);

            if (empty($allocation)) {
                throw new \Exception("Not enough venue capacity for cross-catering {$courseCode}.");
            }

            foreach ($faculties as $f) {
                $created = ExaminationTimetable::create([
                    'exam_setup_id'  => (int)$setup->id,
                    'program_id'     => (int)$f->program_id,
                    'faculty_id'     => (int)$f->id,
                    'course_code'    => $courseCode,
                    'exam_date'      => $examDate,
                    'start_time'     => $startTime,
                    'end_time'       => $endTime,

                    // ✅ new fields
                    'marking_date'   => $markingDate,
                    'uploading_date' => $uploadingDate,
                    'nature'         => $nature,
                ]);

                $attach = [];
                foreach ($venueIds as $vid) {
                    $attach[$vid] = ['allocated_capacity' => (int)($allocation[$vid][$created->faculty_id] ?? 0)];
                }
                $created->venues()->sync($attach);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'type' => 'cross',
                'message' => 'Cross-catering exam updated for all classes.'
            ]);

        } else {
            // normal update -> update only this row
            $faculty = Faculty::findOrFail((int)$validated['faculty_id']);

            // remove any previous same slot for this faculty+course, excluding this row
            $this->deleteExistingSlotExams(
                (int)$setup->id,
                $courseCode,
                $examDate,
                $startTime,
                $endTime,
                [(int)$faculty->id],
                (int)$timetable->id
            );

            $timetable->program_id     = (int)$faculty->program_id;
            $timetable->faculty_id     = (int)$faculty->id;
            $timetable->course_code    = $courseCode;
            $timetable->exam_date      = $examDate;
            $timetable->start_time     = $startTime;
            $timetable->end_time       = $endTime;

            // ✅ new fields
            $timetable->marking_date   = $markingDate;
            $timetable->uploading_date = $uploadingDate;
            $timetable->nature         = $nature;

            $timetable->save();

            $students    = (int)($faculty->total_students_no ?? 0);
            $venueModels = Venue::whereIn('id', $venueIds)->get();
            $allocation  = $this->distributeStudentsAcrossVenues([(int)$faculty->id => $students], $venueModels);

            if (empty($allocation)) {
                throw new \Exception("Not enough venue capacity for {$courseCode}.");
            }

            $attach = [];
            foreach ($venueIds as $vid) {
                $attach[$vid] = ['allocated_capacity' => (int)($allocation[$vid][(int)$faculty->id] ?? 0)];
            }
            $timetable->venues()->sync($attach);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'type' => 'normal',
                'message' => 'Exam updated successfully.'
            ]);
        }

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
    }
}


   public function destroy(ExaminationTimetable $timetable)
{
    DB::beginTransaction();
    try {
        $setupId   = (int)$timetable->exam_setup_id;
        $course    = $timetable->course_code;
        $examDate  = Carbon::parse($timetable->exam_date)->format('Y-m-d');
        $startTime = Carbon::parse($timetable->start_time)->format('H:i:s');
        $endTime   = Carbon::parse($timetable->end_time)->format('H:i:s');

        $isCross = $this->isCrossCateringCourse($course);

        if ($isCross) {
            // delete all related faculties for same slot/course/setup
            $faculties = $this->getFacultiesForCourse($course);
            $facultyIds = $faculties->pluck('id')->map(fn($x)=>(int)$x)->all();

            $this->deleteExistingSlotExams($setupId, $course, $examDate, $startTime, $endTime, $facultyIds);
        } else {
            $timetable->delete();
        }

        DB::commit();
        return back()->with('success', 'Exam deleted successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}


    // ----------------------------
    // AJAX SHOW (for View Exam Modal)
    // ----------------------------

    public function show($id)
{
    $timetable = ExaminationTimetable::with([
        'course',
        'program',
        'faculty',
        'venues',
        'supervisors',
    ])->findOrFail($id);

    return response()->json([
        'id' => $timetable->id,

        // ✅ important ids for edit modal
        'exam_setup_id' => (int) $timetable->exam_setup_id,
        'program_id' => (int) $timetable->program_id,
        'faculty_id' => (int) $timetable->faculty_id,

        'course_code' => $timetable->course_code,
        'exam_date' => $timetable->exam_date,
        'start_time' => $timetable->start_time,
        'end_time' => $timetable->end_time,

        // ✅ new fields
        'marking_date' => $timetable->marking_date ? Carbon::parse($timetable->marking_date)->format('Y-m-d') : null,
        'uploading_date' => $timetable->uploading_date ? Carbon::parse($timetable->uploading_date)->format('Y-m-d') : null,
        'nature' => $timetable->nature ?? 'Theory',

        'course' => $timetable->course ? [
            'name' => $timetable->course->name,
        ] : null,

        'program' => $timetable->program ? [
            'short_name' => $timetable->program->short_name,
            'name' => $timetable->program->name,
        ] : null,

        'faculty' => $timetable->faculty ? [
            'name' => $timetable->faculty->name,
        ] : null,

        'venues' => $timetable->venues->map(function ($v) {
            return [
                'id' => $v->id,
                'name' => $v->name,
                'pivot' => [
                    'allocated_capacity' => $v->pivot->allocated_capacity ?? null
                ]
            ];
        })->values(),

        'supervisors' => $timetable->supervisors->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'pivot' => [
                    'supervisor_role' => $s->pivot->supervisor_role ?? 'Invigilator'
                ]
            ];
        })->values(),
    ]);
}

    // ----------------------------
    // DROPDOWNS / HELPERS
    // ----------------------------

    public function getFacultyByProgramYear($programId, $yearNum)
    {
        $faculty = Faculty::where('program_id', $programId)
            ->where('name', 'LIKE', "% {$yearNum}")
            ->first();

        if (!$faculty) {
            return response()->json(['id' => null, 'name' => 'Invalid year']);
        }

        return response()->json([
            'id' => $faculty->id,
            'name' => $faculty->name,
        ]);
    }

    public function getFacultyCourses(Request $request)
    {
        $facultyId = $request->query('faculty_id');

        $courses = Course::whereHas('faculties', fn ($q) => $q->where('faculties.id', $facultyId))
            ->select('course_code', 'name', 'cross_catering')
            ->orderBy('course_code')
            ->get()
            ->map(fn ($c) => [
                'course_code' => $c->course_code,
                'name' => $c->name,
                'cross_catering' => (bool) $c->cross_catering,
            ])
            ->toArray();

        return response()->json(['course_codes' => $courses]);
    }


    public function getFacultyGroups(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $groups = FacultyGroup::where('faculty_id', $facultyId)
            ->select('group_name', 'student_count')
            ->get()
            ->toArray();

        return response()->json(['groups' => $groups]);
    }

    private function getFacultiesForCourse(string $courseCode)
    {
        return Course::where('course_code', $courseCode)
            ->firstOrFail()
            ->faculties()
            ->select('faculties.id', 'faculties.program_id', 'faculties.total_students_no')
            ->get();
    }

    public function getFacultiesByProgram(Request $request)
    {
        $programId = $request->query('program_id');

        if ($programId === 'all' || !$programId) {
            $faculties = Faculty::select('id', 'name')->orderBy('name')->get();
        } else {
            $faculties = Faculty::where('program_id', $programId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return response()->json(['faculties' => $faculties]);
    }

    public function getSupervisors(Request $request)
    {
        $programId = $request->query('program_id');
        $facultyId = $request->query('faculty_id');

        $query = User::query()
            ->select('id', 'name')
            ->orderBy('name');

        // optional filters
        if ($programId && $programId !== 'all') {
            $query->whereHas('courses.faculties.program', function ($q) use ($programId) {
                $q->where('programs.id', $programId);
            });
        }
        if ($facultyId && $facultyId !== 'all') {
            $query->whereHas('courses.faculties', function ($q) use ($facultyId) {
                $q->where('faculties.id', $facultyId);
            });
        }

        return response()->json(['supervisors' => $query->get()]);
    }

    public function getCourseLecturers(Request $request)
    {
        $courseCode = $request->query('course_code');
        $timetableId = $request->query('timetable_id', null);

        $course = Course::where('course_code', $courseCode)->first();
        if (!$course) {
            return response()->json(['lecturers' => []]);
        }

        $lecturers = $course->lecturers()->select('users.id', 'users.name')->get()->toArray();

        if ($timetableId) {
            $timetable = ExaminationTimetable::with('lecturers')->find($timetableId);
            $assigned = $timetable ? $timetable->lecturers->pluck('id')->toArray() : [];
            $lecturers = array_map(function ($l) use ($assigned) {
                $l['selected'] = in_array($l['id'], $assigned);
                return $l;
            }, $lecturers);
        }

        return response()->json(['lecturers' => $lecturers]);
    }

    private function isCrossCateringCourse(string $courseCode): bool
    {
        $course = Course::where('course_code', $courseCode)->first();
        if (!$course) {
            throw new \Exception("Course {$courseCode} was deleted. Please refresh and select again.");
        }
        return (bool) $course->cross_catering;
    }


    // ----------------------------
    // GENERATION (NO CAPACITY FILTERING + OPTION A)
    // ----------------------------

    public function generateTimetable(Request $request)
{
    Log::info('Generate timetable request:', $request->all());

    try {
        $validated = $request->validate([
            'exam_setup_id'   => 'required|exists:exam_setups,id',
            'program_id'      => 'required|integer|exists:programs,id',
            'faculty_id'      => 'nullable',
            'venue_strategy'  => 'required|in:distribute,single',
            'selected_venues' => 'nullable|array',
            'selected_venues.*' => 'exists:venues,id',

            // ✅ NEW (Generate only these 2; nature auto = Theory)
            'marking_date'    => 'required|date',
            'uploading_date'  => 'required|date|after_or_equal:marking_date',
        ]);

        // ✅ normalize generate meta
        $markingDate   = Carbon::parse($validated['marking_date'])->format('Y-m-d');
        $uploadingDate = Carbon::parse($validated['uploading_date'])->format('Y-m-d');
        $nature        = 'Theory';

        $venuesQuery = Venue::query()->select('id', 'name', 'capacity');

        if (!empty($validated['selected_venues'])) {
            // FORCE venues from modal (single strategy)
            $venuesQuery->whereIn('id', $validated['selected_venues']);
        }

        $venues = $venuesQuery->get();

        if ($venues->isEmpty()) {
            return response()->json(['errors' => ['venues' => 'No venues selected/available.']], 422);
        }

        // NOTE: program_id is validated as integer, so "all" should never reach here
        $setup = ExamSetup::findOrFail((int)$validated['exam_setup_id']);

        // ✅ Preload existing exams for this setup to avoid re-generating duplicates
$existing = ExaminationTimetable::where('exam_setup_id', (int)$setup->id)
    ->select('course_code', 'faculty_id')
    ->get();

// For cross-catering: if any row exists for course_code => consider generated already
$existingCrossCourse = [];
foreach ($existing as $row) {
    $existingCrossCourse[$row->course_code] = true;
}

// For normal courses: prevent regenerating same (faculty_id + course_code)
$existingFacultyCourse = [];
foreach ($existing as $row) {
    $existingFacultyCourse[(int)$row->faculty_id . '|' . $row->course_code] = true;
}

// Optional: count skipped (for response message)
$skippedCross = 0;
$skippedNormal = 0;

        $facultiesQuery = Faculty::query()->where('program_id', (int)$validated['program_id']);

        if (!empty($validated['faculty_id']) && $validated['faculty_id'] !== 'all') {
            $facultiesQuery->where('id', (int)$validated['faculty_id']);
        }

        $faculties = $facultiesQuery->get();

        $days = $this->getValidDates($setup);

        $timeSlots = collect($setup->time_slots ?? [])->map(function ($slot) {
            return [
                'name' => $slot['name'] ?? null,
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
            ];
        })->toArray();

        if (empty($days) || empty($timeSlots)) {
            return response()->json(['errors' => ['setup' => 'Setup has no valid days or no time slots.']], 422);
        }

        // Build sessions:
        // - Non cross-catering: separate by (course_code + faculty_id)
        // - Cross-catering: group by course_code only (multi faculties together)
        $courseGroups = [];

        foreach ($faculties as $faculty) {
            $courses = Course::whereHas('faculties', function ($q) use ($faculty) {
                $q->where('faculties.id', $faculty->id);
            })->get();

                foreach ($courses as $course) {
    $lecturerIds = $course->lecturers()->pluck('id')->toArray();

    if ($course->cross_catering) {

        // ✅ SKIP if this cross course already exists in this setup
        if (isset($existingCrossCourse[$course->course_code])) {
            $skippedCross++;
            continue;
        }

        $allFaculties = $course->faculties()->select('faculties.id','faculties.program_id')->get();

        $key = $course->course_code;

        $courseGroups[$key]['course_code'] = $course->course_code;
        $courseGroups[$key]['course_name'] = $course->name;
        $courseGroups[$key]['cross_catering'] = true;

        foreach ($allFaculties as $f) {
            $courseGroups[$key]['faculties'][] = [
                'faculty_id'   => $f->id,
                'program_id'   => $f->program_id,
                'lecturer_ids' => $lecturerIds,
            ];
        }

    } else {

        // ✅ SKIP if this faculty already has this course in this setup
        $keyCheck = (int)$faculty->id . '|' . $course->course_code;
        if (isset($existingFacultyCourse[$keyCheck])) {
            $skippedNormal++;
            continue;
        }

        $key = $course->course_code . '|' . $faculty->id;

        $courseGroups[$key]['course_code'] = $course->course_code;
        $courseGroups[$key]['course_name'] = $course->name;
        $courseGroups[$key]['cross_catering'] = false;

        $courseGroups[$key]['faculties'][] = [
            'faculty_id'   => $faculty->id,
            'program_id'   => $faculty->program_id,
            'lecturer_ids' => $lecturerIds,
        ];
    }
}
        }

        $sessions = array_values($courseGroups);

        $result = $this->scheduleTimetable($sessions, $days, $timeSlots, $venues);

        if (empty($result['timetables'])) {
            return response()->json([
                'errors' => [
                    'scheduling' => !empty($result['errors'])
                        ? implode(' ', $result['errors'])
                        : 'Unable to generate a conflict-free timetable.'
                ]
            ], 422);
        }

        DB::beginTransaction();
        try {
            $createdTimetables = [];

            foreach ($result['timetables'] as $entry) {
                // create one exam row per faculty, attach SAME venues list to each row.
                foreach ($entry['faculty_rows'] as $row) {
                    $facultyModel = Faculty::findOrFail((int)$row['faculty_id']);

                    $created = ExaminationTimetable::create([
                        'exam_setup_id'  => (int)$setup->id,
                        'program_id'     => (int)$facultyModel->program_id,
                        'faculty_id'     => (int)$row['faculty_id'],
                        'course_code'    => $entry['course_code'],
                        'exam_date'      => $entry['exam_date'],
                        'start_time'     => $entry['start_time'],
                        'end_time'       => $entry['end_time'],

                        // ✅ NEW META saved for generated exams
                        'marking_date'   => $markingDate,
                        'uploading_date' => $uploadingDate,
                        'nature'         => $nature, // Theory
                    ]);

                    $created->lecturers()->sync($row['lecturer_ids'] ?? []);

                    // build faculty student map using total_students_no
                    $facultyStudentMap = [];
                    foreach ($entry['faculty_rows'] as $r) {
                        $f = Faculty::find((int)$r['faculty_id']);
                        $facultyStudentMap[(int)$r['faculty_id']] = (int)($f->total_students_no ?? 0);
                    }

                    // Only venues you selected
                    $venueModels = $venues->whereIn('id', $entry['venue_ids'])->values();

                    // distribute
                    $allocation = $this->distributeStudentsAcrossVenues($facultyStudentMap, $venueModels);
                    if (empty($allocation)) {
                        throw new \Exception(
                            "Not enough venue capacity for {$entry['course_code']} on {$entry['exam_date']} {$entry['start_time']}."
                        );
                    }

                    // attach for THIS faculty
                    $attachData = [];
                    foreach ($entry['venue_ids'] as $vid) {
                        $attachData[$vid] = [
                            'allocated_capacity' => (int)($allocation[$vid][(int)$row['faculty_id']] ?? 0)
                        ];
                    }
                    $created->venues()->sync($attachData);

                    $createdTimetables[] = $created;
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Timetable generated successfully. Skipped cross: {$skippedCross}, skipped normal: {$skippedNormal}.",
                'timetables' => $createdTimetables
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving generated timetable', ['error' => $e->getMessage()]);
            return response()->json(['errors' => ['database' => 'Failed to save timetable: ' . $e->getMessage()]], 422);
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error('Unexpected error in generateTimetable', ['error' => $e->getMessage()]);
        return response()->json(['errors' => ['error' => 'Unexpected error: ' . $e->getMessage()]], 422);
    }
}
    

    private function distributeStudentsAcrossVenues(array $facultyStudentMap, $venues): array
{
    $venueCaps = $venues->map(fn($v) => [
        'id' => (int) $v->id,
        'cap' => (int) floor($v->capacity * 0.75),
    ])
    ->filter(fn($x) => $x['cap'] > 0)
    ->sortBy('cap')  // SMALL to BIG helps reduce waste
    ->values()
    ->all();

    $remaining = $facultyStudentMap; // faculty_id => students
    $alloc = []; // venue_id => faculty_id => allocated

    foreach ($venueCaps as $vc) {
        $vid = $vc['id'];
        $cap = $vc['cap'];
        $alloc[$vid] = [];

        foreach ($remaining as $fid => $need) {
            if ($cap <= 0) break;
            if ($need <= 0) continue;

            $give = min($need, $cap);
            $alloc[$vid][$fid] = ($alloc[$vid][$fid] ?? 0) + $give;

            $remaining[$fid] -= $give;
            $cap -= $give;
        }
    }

    $still = array_sum(array_map(fn($x) => max(0, (int)$x), $remaining));
    if ($still > 0) return []; // IMPORTANT: no partial allocation

    return $alloc;
}


    /**
     * Scheduler Rules Implemented:
     * - NO capacity filtering (student_count ignored)
     * - Venue cannot be double-booked in same day+slot
     * - Lecturer cannot be double-booked in same day+slot
     * - Max 2 exams per faculty per day
     * - Slot balancing: shuffle days and slots per session to avoid always first day/slot
     * - Cross-catering: schedule once (same day+slot+venues) for all faculties in that session
     */
    private function scheduleTimetable(array $sessions, array $days, array $timeSlots, $venues): array
{
    $timetables = [];
    $errors = [];

    $usedSlots = [];
    $lecturerSlots = [];
    $courseSlots = [];
    $facultyDayCount = [];

    $facultySlots = []; // ✅ NEW: prevents 2 courses for same faculty in same slot

    $MAX_EXAMS_PER_FACULTY_PER_DAY = 2;

    shuffle($sessions);

    foreach ($sessions as $session) {
        $courseCode = $session['course_code'];
        $facRows = $session['faculties'] ?? [];
        $cross = (bool)($session['cross_catering'] ?? false);

        $scheduled = false;
        $sessionConflicts = [];

        $daysTry = $days;
        $slotsTry = $timeSlots;
        shuffle($daysTry);
        shuffle($slotsTry);

        foreach ($daysTry as $day) {
            foreach ($slotsTry as $slot) {
                $startTime = $slot['start_time'];
                $endTime = $slot['end_time'];

                // course already scheduled in same slot
                if (isset($courseSlots[$courseCode][$day][$startTime])) {
                    $sessionConflicts[] = "Course {$courseCode} already scheduled on {$day} at {$startTime}.";
                    continue;
                }

                // ✅ NEW: faculty slot conflict (THIS is your issue)
                $slotBlocked = false;
                foreach ($facRows as $r) {
                    $fid = (int)$r['faculty_id'];
                    if (isset($facultySlots[$fid][$day][$startTime])) {
                        $sessionConflicts[] = "Faculty {$fid} already has an exam on {$day} at {$startTime}.";
                        $slotBlocked = true;
                        break;
                    }
                }
                if ($slotBlocked) continue;

                // max/day check
                $blocked = false;
                foreach ($facRows as $r) {
                    $fid = (int)$r['faculty_id'];
                    $count = $facultyDayCount[$fid][$day] ?? 0;
                    if ($count >= $MAX_EXAMS_PER_FACULTY_PER_DAY) {
                        $sessionConflicts[] = "Faculty {$fid} already has {$count} exam(s) on {$day} (max {$MAX_EXAMS_PER_FACULTY_PER_DAY}).";
                        $blocked = true;
                        break;
                    }
                }
                if ($blocked) continue;

                // lecturer slot conflict
                $lecturerBlocked = false;
                foreach ($facRows as $r) {
                    foreach (($r['lecturer_ids'] ?? []) as $lid) {
                        if (isset($lecturerSlots[$lid][$day][$startTime])) {
                            $sessionConflicts[] = "Lecturer {$lid} unavailable on {$day} at {$startTime}.";
                            $lecturerBlocked = true;
                            break 2;
                        }
                    }
                }
                if ($lecturerBlocked) continue;

                // available venues
                $availableVenues = $venues->filter(function ($venue) use ($usedSlots, $day, $startTime) {
                    return !isset($usedSlots[$day][$startTime][$venue->id]);
                })->values();

                // total students
                $totalStudents = 0;
                foreach ($facRows as $r) {
                    $f = Faculty::find($r['faculty_id']);
                    $totalStudents += (int)($f->total_students_no ?? 0);
                }

                $selectedVenueIds = $this->pickVenuesToFitStudents($totalStudents, $availableVenues);
                if (empty($selectedVenueIds)) {
                    $sessionConflicts[] = "Not enough venue capacity on {$day} at {$startTime} for {$courseCode}.";
                    continue;
                }

                // DB conflict check (IMPORTANT: will be improved below too)
                $hasDbConflict = false;
                foreach ($facRows as $r) {
                    $timetableData = [
                        'exam_date' => $day,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'course_code' => $courseCode,
                        'faculty_id' => $r['faculty_id'],
                        'venue_id' => $selectedVenueIds,
                        'lecturer_ids' => $r['lecturer_ids'] ?? [],
                        'group_selection' => [],
                    ];

                    $conf = $this->checkConflicts(new Request($timetableData), $timetableData);
                    if (!empty($conf)) {
                        $sessionConflicts = array_merge($sessionConflicts, $conf);
                        $hasDbConflict = true;
                        break;
                    }
                }
                if ($hasDbConflict) continue;

                // save session result
                $timetables[] = [
                    'course_code' => $courseCode,
                    'exam_date' => $day,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'venue_ids' => $selectedVenueIds,
                    'faculty_rows' => $facRows,
                ];

                // trackers
                foreach ($selectedVenueIds as $vid) {
                    $usedSlots[$day][$startTime][$vid] = true;
                }

                foreach ($facRows as $r) {
                    $fid = (int)$r['faculty_id'];

                    // ✅ NEW: lock faculty slot
                    $facultySlots[$fid][$day][$startTime] = true;

                    foreach (($r['lecturer_ids'] ?? []) as $lid) {
                        $lecturerSlots[$lid][$day][$startTime] = true;
                    }

                    $facultyDayCount[$fid][$day] = ($facultyDayCount[$fid][$day] ?? 0) + 1;
                }

                $courseSlots[$courseCode][$day][$startTime] = true;

                $scheduled = true;
                break;
            }

            if ($scheduled) break;
        }

        if (!$scheduled) {
            $errors[] = "Cannot schedule {$courseCode}: " . implode(' ', array_unique($sessionConflicts));
        }
    }

    return [
        'timetables' => $timetables,
        'errors' => $errors,
    ];
}

     private function pickVenuesToFitStudents(int $totalStudents, $availableVenues): array
{
    // effective capacity = 75%
    $venues = $availableVenues->map(function ($v) {
        return (object)[
            'id' => (int)$v->id,
            'name' => $v->name,
            'eff' => (int) floor($v->capacity * 0.75),
        ];
    })->filter(fn($v) => $v->eff > 0)->values();

    $selected = [];
    $remaining = $totalStudents;

    // Best-fit loop: each time choose venue with smallest eff >= remaining.
    while ($remaining > 0 && $venues->isNotEmpty()) {

        // candidates that can finish the remaining
        $candidates = $venues->filter(fn($v) => $v->eff >= $remaining)->sortBy('eff')->values();

        if ($candidates->isNotEmpty()) {
            $chosen = $candidates->first(); // smallest that fits
        } else {
            // none can finish it -> take the largest available to reduce remaining fastest
            $chosen = $venues->sortByDesc('eff')->first();
        }

        $selected[] = $chosen;
        $remaining -= min($remaining, $chosen->eff);

        // remove chosen from pool
        $venues = $venues->reject(fn($v) => $v->id === $chosen->id)->values();
    }

    // if still > 0 => not enough capacity overall
    if ($remaining > 0) return [];

    return array_map(fn($v) => $v->id, $selected);
}

    // ----------------------------
    // CONFLICT CHECKER (NO CAPACITY)
    // ----------------------------

private function checkConflicts(Request $request, array $validated, ?int $excludeId = null): array
{
    $conflicts = [];

    $venueIds = is_array($validated['venue_id'] ?? null) ? $validated['venue_id'] : [$validated['venue_id'] ?? null];
    $venueIds = array_values(array_filter(array_map('intval', $venueIds)));

    $validated['start_time'] = Carbon::parse($validated['start_time'])->format('H:i:s');
    $validated['end_time']   = Carbon::parse($validated['end_time'])->format('H:i:s');
    $validated['exam_date']  = Carbon::parse($validated['exam_date'])->format('Y-m-d');

    // ✅ 1) Faculty Conflict
    $facultyConflict = ExaminationTimetable::whereDate('exam_date', $validated['exam_date'])
        ->where('faculty_id', (int)$validated['faculty_id'])
        ->where(function ($q) use ($validated) {
            $q->where('start_time', '<', $validated['end_time'])
              ->where('end_time',   '>', $validated['start_time']);
        })
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->first();

    if ($facultyConflict) {
        $conflicts[] = "Faculty already has another exam at the same time.";
    }

    // ✅ 2) Lecturer Conflict
    if (!empty($validated['lecturer_ids'])) {
        $lecturerConflict = ExaminationTimetable::whereDate('exam_date', $validated['exam_date'])
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                  ->where('end_time',   '>', $validated['start_time']);
            })
            ->whereHas('lecturers', fn($q) => $q->whereIn('users.id', $validated['lecturer_ids']))
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();

        if ($lecturerConflict) {
            $conflicts[] = "One of selected lecturers has another exam at the same time.";
        }
    }

   

    // ✅ 4) Venue Conflict
    foreach ($venueIds as $venueId) {
        $venueConflict = ExaminationTimetable::whereDate('exam_date', $validated['exam_date'])
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                  ->where('end_time',   '>', $validated['start_time']);
            })
            ->whereHas('venues', fn($q) => $q->where('venues.id', $venueId))
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();

        if ($venueConflict) {
            $conflicts[] = "Venue {$venueId} is already in use at the same time.";
            break;
        }
    }

    // ✅ 5) Same course duplicated in same slot (same faculty)
    $courseConflict = ExaminationTimetable::whereDate('exam_date', $validated['exam_date'])
        ->where('course_code', $validated['course_code'])
        ->where('faculty_id', (int)$validated['faculty_id'])
        ->where(function ($q) use ($validated) {
            $q->where('start_time', '<', $validated['end_time'])
              ->where('end_time',   '>', $validated['start_time']);
        })
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->first();

    if ($courseConflict) {
        $conflicts[] = "Course {$validated['course_code']} already scheduled for this faculty at same time.";
    }

    return $conflicts;
}

    

    // ----------------------------
    // DATE HELPERS
    // ----------------------------

    private function getValidDates(ExamSetup $setup): array
{
    $dates = [];
    $start = Carbon::parse($setup->start_date);
    $end   = Carbon::parse($setup->end_date);

    $includeWeekends = (bool) ($setup->include_weekends ?? false);

    for ($d = $start->copy(); $d->lte($end); $d->addDay()) {

        // EXCLUDE SAT/SUN if include_weekends = false
        if (!$includeWeekends && $d->isWeekend()) {
            continue;
        }

        $dates[] = $d->format('Y-m-d');
    }

    return $dates;
}

    // still here if you want later (currently not used for filtering)
    private function calculateStudentCount($faculty, $groupSelection)
    {
        if (!$faculty) return 0;
        return (int)($faculty->total_students_no ?? 0);
    }

    // keep old pdf method if you want later
    public function generatePdf(Request $request)
    {
        return back()->with('error', 'PDF export not implemented yet.');
    }

 



    private function deleteExistingSlotExams(
    int $setupId,
    string $courseCode,
    string $examDate,
    string $startTime,
    string $endTime,
    ?array $facultyIds = null,
    ?int $excludeId = null
): void {
    $q = ExaminationTimetable::where('exam_setup_id', $setupId)
        ->where('course_code', $courseCode)
        ->whereDate('exam_date', $examDate)
        ->where('start_time', $startTime)
        ->where('end_time', $endTime);

    if ($facultyIds) {
        $q->whereIn('faculty_id', $facultyIds);
    }

    if ($excludeId) {
        $q->where('id', '!=', $excludeId);
    }

    $q->delete(); // pivots should cascade if FK; if not, detach first in model events
}
}
