<?php

namespace App\Http\Controllers;

use App\Exports\FacultiesExport;
use App\Imports\FacultiesImport;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Course;
use App\Models\FacultyGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FacultyController extends Controller
{
    public function index(Request $request)
    {
        $programId = $request->query('program_id');
        $query = Program::with(['faculties' => function ($query) {
            $query->with('program')->orderBy('name');
        }])->orderBy('name');

        if ($programId) {
            $query->where('id', $programId);
        }

        $programs = $query->paginate(5); // Paginate to show 5 programs per page
        $allPrograms = Program::orderBy('name')->get(); // For dropdown

        return view('faculties.index', compact('programs', 'allPrograms', 'programId'));
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

        $desired = 40;
        $total = $request->total_students_no;
        $num_groups = $total > 0 ? floor($total / $desired) : 0;
        $remaining = $total - $num_groups * $desired;

        if ($num_groups > 0) {
            if ($remaining >= $desired / 2) {
                $num_groups++;
                for ($i = 0; $i < $num_groups; $i++) {
                    $count = ($i < $num_groups - 1) ? $desired : $remaining;
                    $name = $this->generateGroupName($i);

                    FacultyGroup::create([
                        'faculty_id' => $faculty->id,
                        'group_name' => $name,
                        'student_count' => $count,
                    ]);
                }
            } else {
                $base_count = $desired;
                $add_extra = $remaining > 0 ? floor($remaining / $num_groups) : 0;
                $base_count += $add_extra;
                $remaining -= $add_extra * $num_groups;

                for ($i = 0; $i < $num_groups; $i++) {
                    $count = $base_count;
                    if ($i < $remaining) {
                        $count++;
                    }
                    $name = $this->generateGroupName($i);

                    FacultyGroup::create([
                        'faculty_id' => $faculty->id,
                        'group_name' => $name,
                        'student_count' => $count,
                    ]);
                }
            }
        } elseif ($remaining > 0) {
            FacultyGroup::create([
                'faculty_id' => $faculty->id,
                'group_name' => $this->generateGroupName(0),
                'student_count' => $remaining,
            ]);
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
        ]);

        $faculty->update($request->only([
            'name',
            'total_students_no',
            'description',
            'program_id',
        ]));

        $faculty->courses()->sync($request->course_ids ?: []);

        $desired = 40;
        $total = $request->total_students_no;
        $num_groups = $total > 0 ? floor($total / $desired) : 0;
        $remaining = $total - $num_groups * $desired;

        if ($num_groups > 0 || $remaining > 0) {
            $faculty->groups()->delete();
            if ($num_groups > 0) {
                if ($remaining >= $desired / 2) {
                    $num_groups++;
                    for ($i = 0; $i < $num_groups; $i++) {
                        $count = ($i < $num_groups - 1) ? $desired : $remaining;
                        $name = $this->generateGroupName($i);

                        FacultyGroup::create([
                            'faculty_id' => $faculty->id,
                            'group_name' => $name,
                            'student_count' => $count,
                        ]);
                    }
                } else {
                    $base_count = $desired;
                    $add_extra = $remaining > 0 ? floor($remaining / $num_groups) : 0;
                    $base_count += $add_extra;
                    $remaining -= $add_extra * $num_groups;

                    for ($i = 0; $i < $num_groups; $i++) {
                        $count = $base_count;
                        if ($i < $remaining) {
                            $count++;
                        }
                        $name = $this->generateGroupName($i);

                        FacultyGroup::create([
                            'faculty_id' => $faculty->id,
                            'group_name' => $name,
                            'student_count' => $count,
                        ]);
                    }
                }
            } elseif ($remaining > 0) {
                FacultyGroup::create([
                    'faculty_id' => $faculty->id,
                    'group_name' => $this->generateGroupName(0),
                    'student_count' => $remaining,
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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new FacultiesImport, $request->file('file'));

        return redirect()->route('faculties.index')->with('success', 'Faculties imported successfully.');
    }

    public function export()
    {
        return Excel::download(new FacultiesExport, 'faculties.xlsx');
    }

    private function generateGroupName($index)
    {
        $name = '';
        $i = $index;
        while ($i >= 0) {
            $name = chr(65 + ($i % 26)) . $name;
            $i = floor($i / 26) - 1;
        }
        return 'Group ' . $name;
    }
}