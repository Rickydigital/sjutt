<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $courses = Course::query()
            ->when($search, function ($query, $search) {
                return $query->where('school_faculty', 'like', "%{$search}%")
                             ->orWhere('academic_programme', 'like', "%{$search}%")
                             ->orWhere('entry_qualifications', 'like', "%{$search}%")
                             ->orWhere('tuition_fee_per_year', 'like', "%{$search}%")
                             ->orWhere('duration', 'like', "%{$search}%");
            })
            ->paginate(10);

        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        return view('admin.courses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_faculty' => 'required|string|max:255',
            'academic_programme' => 'required|string|max:255',
            'entry_qualifications' => 'required|string',
            'tuition_fee_per_year' => 'required|numeric',
            'duration' => 'required|string|max:100',
        ]);

        try {
            Course::create($request->all());
            return redirect()->route('courses.index')->with('success', 'Course created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create course: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Course $course)
    {
        return view('admin.courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'school_faculty' => 'required|string|max:255',
            'academic_programme' => 'required|string|max:255',
            'entry_qualifications' => 'required|string',
            'tuition_fee_per_year' => 'required|numeric',
            'duration' => 'required|string|max:100',
        ]);

        $course->update($request->all());
        return redirect()->route('courses.index')->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('courses.index')->with('success', 'Course deleted successfully.');
    }
}
