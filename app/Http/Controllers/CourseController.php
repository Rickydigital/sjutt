<?php

namespace App\Http\Controllers;

use App\Exports\CoursesExport;
use App\Imports\CoursesImport;
use App\Models\Program;
use App\Models\Course;
use App\Models\User;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('lecturers')->paginate(10);
        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        $lecturers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Lecturer');
        })->get();
        $faculties = Faculty::all();
        return view('admin.courses.create', compact('lecturers', 'faculties'));
    }

    public function store(Request $request)
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

        return redirect()->route('courses.index')->with('success', 'Course created successfully.');
    }

    public function edit(Course $course)
    {
        $lecturers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Lecturer');
        })->get();
        $faculties = Faculty::all();
        return view('admin.courses.edit', compact('course', 'lecturers', 'faculties'));
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'course_code' => 'required|string|max:255|unique:courses,course_code,' . $course->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'lecturer_ids' => 'nullable|array',
            'lecturer_ids.*' => 'exists:users,id',
            'faculty_ids' => 'nullable|array',
            'faculty_ids.*' => 'exists:faculties,id',
        ]);

        $course->update($request->only(['course_code', 'name', 'description', 'credits']));
        $course->lecturers()->sync($request->lecturer_ids ?: []);
        $course->faculties()->sync($request->faculty_ids ?: []);

        return redirect()->route('courses.index')->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('courses.index')->with('success', 'Course deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new CoursesImport, $request->file('file'));

        return back()->with('success', 'Courses imported successfully!');
    }

    public function export()
    {
        return Excel::download(new CoursesExport, 'courses_template.xlsx');
    }
}