<?php

namespace App\Http\Controllers\StudentWeb\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StudentLoginController extends Controller
{
    public function showLogin()
    {
        return view('stu.login');
    }



public function login(Request $request)
{
    $validated = $request->validate([
        'reg_no'   => 'required|string',
        'password' => 'required|string',
    ]);

    $student = Student::where('reg_no', $validated['reg_no'])->first();

    if (!$student || !Hash::check($validated['password'], $student->password)) {
        return back()
            ->withErrors(['reg_no' => 'Invalid credentials'])
            ->withInput();
    }

    if ($student->status !== 'Active') {
        return back()
            ->withErrors(['reg_no' => 'Please activate your account in mobile app.'])
            ->withInput();
    }

    // Login student (using the officer guard)
    Auth::guard('stuofficer')->login($student, true);

    // ── DECISION POINT ─────────────────────────────────────────────
    // If user is assigned as officer in ANY election (even inactive ones)
    if ($student->isOfficer()) {
        return redirect()
            ->route('officer.dashboard')
            ->with('success', 'Welcome Election Officer!');
    }

    // Everyone else goes to voting
    return redirect()
        ->route('student.vote.index')
        ->with('info', 'Welcome! Proceed to vote.');
}


    public function logout(Request $request)
    {
        Auth::guard('stuofficer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('stu.login')->with('success', 'Logged out.');
    }

    
}
