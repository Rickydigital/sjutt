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
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Display a listing of courses based on user role.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Course::with(['lecturers', 'faculties']);

        // Role-based filtering
        if ($user->hasRole('Lecturer')) {
            $query->whereHas('lecturers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $query->whereHas('faculties', function ($q) use ($programIds) {
                $q->whereHas('programs', function ($q) use ($programIds) {
                    $q->whereIn('programs.id', $programIds);
                });
            });
        } elseif (!$user->hasAnyRole(['Admin', 'Timetable Officer'])) {
            // Other roles (e.g., Student) see no courses
            $query->whereRaw('1 = 0');
        }
        // Admin and Timetable Officer see all courses (no additional filter)

        // Search by course code or name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('course_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filter by faculty
        if ($request->has('faculty_id') && !empty($request->faculty_id)) {
            $query->whereHas('faculties', function ($q) use ($request) {
                $q->where('faculties.id', $request->faculty_id);
            });
        }

        $courses = $query->paginate(8);
        $faculties = Faculty::all();

        return view('admin.courses.index', compact('courses', 'faculties'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action');
        }

        $lecturers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Lecturer');
        })->get();
        $faculties = Faculty::all();

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $faculties = Faculty::whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->get();
        }

        return view('admin.courses.create', compact('lecturers', 'faculties'));
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'course_code' => 'required|string|max:255|unique:courses',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'lecturer_ids' => 'nullable|array',
            'lecturer_ids.*' => 'exists:users,id',
            'faculty_ids' => 'required|array',
            'faculty_ids.*' => 'exists:faculties,id',
        ]);

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $validFacultyIds = Faculty::whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->pluck('id')->toArray();

            if (array_diff($request->faculty_ids, $validFacultyIds)) {
                return redirect()->back()->withErrors(['faculty_ids' => 'Selected faculties are not part of your programs.']);
            }
        }

        $course = Course::create($request->only(['course_code', 'name', 'description', 'credits']));
        if ($request->lecturer_ids) {
            $course->lecturers()->sync($request->lecturer_ids);
        }
        if ($request->faculty_ids) {
            $course->faculties()->sync($request->faculty_ids);
        }

        return redirect()->route('courses.index')->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        $user = Auth::user();
        if ($user->hasRole('Lecturer')) {
            $hasAccess = $course->lecturers()->where('users.id', $user->id)->exists();
            if (!$hasAccess) {
                abort(403, 'Unauthorized action.');
            }
        } elseif ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $hasAccess = $course->faculties()->whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->exists();
            if (!$hasAccess) {
                abort(403, 'Unauthorized action.');
            }
        } elseif (!$user->hasAnyRole(['Admin', 'Timetable Officer'])) {
            abort(403, 'Unauthorized action.');
        }

        return view('admin.courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $hasAccess = $course->faculties()->whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->exists();

            if (!$hasAccess) {
                abort(403, 'You are not authorized to edit this course.');
            }
        }

        $lecturers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Lecturer');
        })->get();
        $faculties = Faculty::all();

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $faculties = Faculty::whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->get();
        }

        return view('admin.courses.edit', compact('course', 'lecturers', 'faculties'));
    }

    /**
     * Update the specified course in storage.
     */
    public function update(Request $request, Course $course)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'course_code' => 'required|string|max:255|unique:courses,course_code,' . $course->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'lecturer_ids' => 'nullable|array',
            'lecturer_ids.*' => 'exists:users,id',
            'faculty_ids' => 'required|array',
            'faculty_ids.*' => 'exists:faculties,id',
        ]);

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $validFacultyIds = Faculty::whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->pluck('id')->toArray();

            if (array_diff($request->faculty_ids, $validFacultyIds)) {
                return redirect()->back()->withErrors(['faculty_ids' => 'Selected faculties are not part of your programs.']);
            }
        }

        $course->update($request->only(['course_code', 'name', 'description', 'credits']));
        $course->lecturers()->sync($request->lecturer_ids ?: []);
        $course->faculties()->sync($request->faculty_ids ?: []);

        return redirect()->route('courses.index')->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy(Course $course)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $hasAccess = $course->faculties()->whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->exists();

            if (!$hasAccess) {
                abort(403, 'You are not authorized to delete this course.');
            }
        }

        $course->delete();
        return redirect()->route('courses.index')->with('success', 'Course deleted successfully.');
    }

    /**
     * Import courses from an Excel file.
     */
    public function import(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new CoursesImport, $request->file('file'));

        return back()->with('success', 'Courses imported successfully!');
    }

    /**
     * Export courses to an Excel file.
     */
    public function export()
    {
        return Excel::download(new CoursesExport, 'courses_template.xlsx');
    }
}