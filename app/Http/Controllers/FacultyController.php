<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Program;
use App\Models\Course;
use App\Models\FacultyGroup;
use App\Models\User;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function index()
    {
        $programs = Program::with(['faculties' => function ($query) {
            $query->with('program')->orderBy('name');
        }])->orderBy('name')->get();
        return view('faculties.index', compact('programs'));
    }

    public function create()
    {
        $programs = Program::all();
        $courses = Course::all();
        $lecturers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Lecturer');
        })->get();
        return view('faculties.create', compact('programs', 'courses', 'lecturers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:faculties',
            'total_students_no' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
            'group_names' => 'nullable|string',
        ]);

        $faculty = Faculty::create($request->only([
            'name',
            'total_students_no',
            'description',
            'program_id',
        ]));

        if ($request->course_ids) {
            $faculty->courses()->sync($request->course_ids);
        }

        if ($request->group_names) {
            $groupNames = array_filter(array_map('trim', explode(',', $request->group_names)));
            $studentCount = $request->total_students_no / max(1, count($groupNames));
            foreach ($groupNames as $name) {
                FacultyGroup::create([
                    'faculty_id' => $faculty->id,
                    'group_name' => $name,
                    'student_count' => floor($studentCount),
                ]);
            }
        }

        return redirect()->route('faculties.index')->with('success', 'Faculty created successfully.');
    }

    public function storeCourse(Request $request)
    {
        $request->validate([
            'course_code' => 'required|string|max:255|unique:courses',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'lecturer_ids' => 'nullable|array',
            'lecturer_ids.*' => 'exists:users,id',
            'faculty_ids' => 'nullable|array',
            'faculty_ids.*' => 'exists:faculties,id',
        ]);

        $course = Course::create($request->only(['course_code', 'name', 'description', 'credits']));
        if ($request->lecturer_ids) {
            $course->lecturers()->sync($request->lecturer_ids);
        }
        if ($request->faculty_ids) {
            $course->faculties()->sync($request->faculty_ids);
        }

        return redirect()->route('faculties.create')
            ->with('success', 'Course created successfully.')
            ->with('new_course_id', $course->id);
    }

    public function show(Faculty $faculty)
    {
        $faculty->load(['program', 'courses', 'groups']);
        return view('faculties.show', compact('faculty'));
    }
     
    public function edit(Faculty $faculty)
{
    return view('faculties.edit', [
        'faculty' => $faculty,
        'programs' => Program::all(),
        'courses' => Course::all(),
        'lecturers' => User::whereHas('roles', fn($q) => $q->where('name', 'lecturer'))->get(),
    ]);
}

    public function update(Request $request, Faculty $faculty)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:faculties,name,' . $faculty->id,
            'total_students_no' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
            'group_names' => 'nullable|string',
        ]);

        $faculty->update($request->only([
            'name',
            'total_students_no',
            'description',
            'program_id',
        ]));

        $faculty->courses()->sync($request->course_ids ?: []);

        if ($request->group_names) {
            $faculty->groups()->delete();
            $groupNames = array_filter(array_map('trim', explode(',', $request->group_names)));
            $studentCount = $request->total_students_no / max(1, count($groupNames));
            foreach ($groupNames as $name) {
                FacultyGroup::create([
                    'faculty_id' => $faculty->id,
                    'group_name' => $name,
                    'student_count' => floor($studentCount),
                ]);
            }
        }

        return redirect()->route('faculties.index')->with('success', 'Faculty updated successfully.');
    }

    public function destroy(Faculty $faculty)
    {
        $faculty->delete();
        return redirect()->route('faculties.index')->with('success', 'Faculty deleted successfully.');
    }

    /**
     * Get available faculty names for a program.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFacultyNames(Request $request)
    {
        $programId = $request->query('program_id');
        $program = Program::find($programId);

        if (!$program) {
            return response()->json(['faculty_names' => []]);
        }

        $generatedNames = $program->getGeneratedFacultyNames();
        $existingNames = Faculty::where('program_id', $programId)->pluck('name')->toArray();
        $availableNames = array_diff($generatedNames, $existingNames);

        return response()->json(['faculty_names' => array_values($availableNames)]);
    }
}