<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Program;
use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Base query
        $query = Student::with(['faculty', 'program'])
            ->select('id', 'first_name', 'last_name', 'reg_no', 'email', 'faculty_id', 'program_id', 'gender');

        // === ROLE-BASED FILTERING ===
        if ($user->hasRole('Lecturer')) {
            // Lecturer sees only students in their courses
            $courseIds = $user->courses()->pluck('courses.id');
            $query->whereHas('program.faculties.courses', function ($q) use ($courseIds) {
                $q->whereIn('courses.id', $courseIds);
            });
        }
        elseif ($user->hasRole('Administrator')) {
            // Administrator sees students in their program(s)
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $query->whereIn('program_id', $programIds);
        }
        else {
            // Admin, Dean Of Students, Timetable Officer → ALL students
            // No extra where()
        }

        // === SEARCH & FILTERS ===
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('reg_no', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($facultyId = $request->input('faculty_id')) {
            $query->where('faculty_id', $facultyId);
        }

        if ($programId = $request->input('program_id')) {
            $query->where('program_id', $programId);
        }

        if ($year = $request->input('year')) {
            $query->whereRaw("SUBSTRING(reg_no, -2) = ?", [str_pad($year, 2, '0', STR_PAD_LEFT)]);
        }

        // === PAGINATION ===
        $students = $query->orderBy('first_name')->paginate(20);

        // === FILTER OPTIONS ===
        $faculties = Faculty::orderBy('name')->get();
        $programs  = Program::orderBy('name')->get();

        return view('students.index', compact('students', 'faculties', 'programs'));
    }
}