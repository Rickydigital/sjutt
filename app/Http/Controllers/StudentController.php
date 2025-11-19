<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Models\Program;
use App\Models\Course;
use App\Models\Faculty;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;


class StudentController extends Controller
{
public function index(Request $request)
{
    $user = Auth::user();

    $query = Student::with(['faculty', 'program'])
        ->select('id', 'first_name', 'last_name', 'reg_no', 'email', 'gender', 'faculty_id', 'program_id', 'status');

    // Your existing role filtering logic...
    if ($user->hasRole('Lecturer')) {
        $courseIds = $user->courses()->pluck('courses.id');
        $query->whereHas('program.faculties.courses', fn($q) => $q->whereIn('courses.id', $courseIds));
    } elseif ($user->hasRole('Administrator')) {
        $programIds = Program::where('administrator_id', $user->id)->pluck('id');
        $query->whereIn('program_id', $programIds);
    }

    // Search & filters...
    if ($search = $request->input('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('reg_no', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    if ($request->filled('faculty_id')) $query->where('faculty_id', $request->faculty_id);
    if ($request->filled('program_id')) $query->where('program_id', $request->program_id);
    if ($request->filled('status')) {
    if ($request->status === 'Inactive') {
        $query->where('status', 'Inactive');
    } else {
        $query->where('status', $request->status);
    }
}

    $students = $query->orderBy('first_name')->paginate(25)->withQueryString();

    // THIS IS THE CORRECT WAY – LOAD FACULTIES PROPERLY
    $programs = Program::with('faculties')->orderBy('name')->get();

    $facultiesByProgram = $programs->mapWithKeys(function ($program) {
        return [
            $program->id => $program->faculties->pluck('name', 'id')->toArray()
        ];
    })->toArray(); // ← toArray() not toJson() here!

    $faculties = Faculty::with('program')->get();

    return view('students.index', compact(
        'students',
        'programs',
        'faculties',
        'facultiesByProgram'
    ));
}



    public function store(Request $request)
    {
        $request->validate([
            'reg_no'      => 'required|string|unique:students,reg_no',
            'first_name'  => 'nullable|string|max:255',
            'last_name'   => 'nullable|string|max:255',
            'email'       => 'nullable|email|unique:students,email',
            'phone' => 'nullable|string|max:20|unique:students,phone',
            'gender'      => 'required|in:male,female',
            'faculty_id'  => 'required|exists:faculties,id',
            'program_id'  => 'required|exists:programs,id',
        ]);

        Student::create([
            'reg_no'      => $request->reg_no,
            'first_name'  => $request->first_name,
            'last_name'   => $request->last_name,
            'phone'       => $request->phone,
            'email'       => $request->email ?: 'temp.' . str_replace('/', '-', $request->reg_no) . '@student.sjut.ac.tz',
            'password'    => Hash::make($request->reg_no), // ← Login with Reg No
            'gender'      => $request->gender,
            'faculty_id'  => $request->faculty_id,
            'program_id'  => $request->program_id,
            'is_online'   => false,
            'can_upload'  => true,
        ]);

        return back()->with('success', "Student created! Login: Email + Reg No ({$request->reg_no})");
    }

    public function update(Request $request, Student $student)
{
    $request->validate([
        'reg_no'      => ['required', 'string', Rule::unique('students')->ignore($student->id)],
        'first_name'  => 'nullable|string|max:255',
        'last_name'   => 'nullable|string|max:255',
        'email'       => ['nullable', 'email', Rule::unique('students')->ignore($student->id)],
        'phone'       => ['nullable', 'string', 'max:20', Rule::unique('students', 'phone')->ignore($student->id)],
        'gender'      => 'required|in:male,female',
        'faculty_id'  => 'required|exists:faculties,id',
        'program_id'  => 'required|exists:programs,id',
        // 'status'      => 'required|in:Active,Inactive,Alumni',
    ]);

    $student->update([
        'reg_no'      => $request->reg_no,
        'first_name'  => $request->first_name,
        'last_name'   => $request->last_name,
        'email'       => $request->email,
        'phone'       => $request->phone,
        'gender'      => $request->gender,
        'faculty_id'  => $request->faculty_id,
        'program_id'  => $request->program_id,
        // 'status'      => $request->status,
    ]);

    return back()->with('success', 'Student updated successfully! Password unchanged.');
}

    public function resetPassword(Request $request, Student $student)
    {
        $student->update([
            'password' => Hash::make('sjut123456')
        ]);

        return back()->with('success', "Password reset to: sjut123456");
    }

    public function import(Request $request)
    {
        // Validate file
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            DB::transaction(function () use ($request) {
                Excel::import(new StudentsImport, $request->file('file'));
            });

            $count = Student::where('created_at', '>=', now()->subMinutes(5))->count(); // rough count of just imported

            return back()->with('success', "Success! Imported {$count} students. They can now login with Email + Reg No as password.");
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();

            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            }

            return back()->with('error', 'Import failed! Check these errors:<br><ul><li>' . implode('</li><li>', $errorMessages) . '</li></ul>');
        } catch (\Exception $e) {
            Log::error('Student Import Failed: ' . $e->getMessage());

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request)
{
    $user = Auth::user();

    // Build query with same role logic as index()
    $query = Student::with(['faculty', 'program']);

    // Role-based filtering (same as index)
    if ($user->hasRole('Lecturer')) {
        $courseIds = $user->courses()->pluck('courses.id');
        $query->whereHas('program.faculties.courses', fn($q) => $q->whereIn('courses.id', $courseIds));
    } elseif ($user->hasRole('Administrator')) {
        $programIds = Program::where('administrator_id', $user->id)->pluck('id');
        $query->whereIn('program_id', $programIds);
    }

    // Apply filters from modal
    if ($request->filled('faculty_ids') && !in_array('', $request->faculty_ids)) {
        $query->whereIn('faculty_id', $request->faculty_ids);
    }
    if ($request->filled('program_ids') && !in_array('', $request->program_ids)) {
        $query->whereIn('program_id', $request->program_ids);
    }
    if ($request->filled('status') && $request->status !== '') {
        $query->where('status', $request->status);
    }

    $students = $query->orderBy('first_name')->get();

    return Excel::download(new StudentsExport($students), 'students_export_' . now()->format('Y-m-d_His') . '.xlsx');
}


public function exportAttendancePdf(Request $request)
{
    $user = Auth::user();

    // Determine faculties based on role
    if ($user->hasRole('Lecturer')) {
        $course = $user->courses()->first();
        $faculty = $course?->program?->faculties?->first();
        $faculties = $faculty ? collect([$faculty]) : collect();
    } else {
        $faculties = Faculty::orderBy('name')->get();
    }

    // Generate next 5 working days (Mon-Fri) from today
    $today = Carbon::today();
    $workingDays = [];
    $current = $today->copy();

    while (count($workingDays) < 5) {
        if ($current->isWeekday()) { // Monday=1 to Friday=5
            $workingDays[] = [
                'date' => $current->format('d/m/Y'),
                'day'  => $current->format('l'),
                'short' => $current->format('D'),
                'is_today' => $current->isToday(),
            ];
        }
        $current->addDay();
    }

    $data = [
        'faculties' => $faculties,
        'workingDays' => $workingDays,
        'today' => $today->format('l, d F Y'),
        'generated_at' => now()->format('d/m/Y H:i'),
    ];

    $pdf = Pdf::loadView('students.pdf.attendance', $data)
              ->setPaper('a4', 'landscape')
              ->setOptions([
                  'isHtml5ParserEnabled' => true,
                  'isRemoteEnabled' => true,
                  'defaultFont' => 'DejaVu Sans',
              ]);

    return $pdf->stream('Attendance_Sheet_' . now()->format('Y-m-d') . '.pdf');
}
}
