<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use Carbon\Carbon;
use App\Models\Student;
use Illuminate\Http\Request;

class ElectionController extends Controller
{
  public function index()
{
    $elections = Election::with(['generalOfficers', 'positions'])->latest()->paginate(15);

    // Only students who are Active
    $activeStudents = Student::where('status', 'Active')
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->get();

    return view('elections.index', compact('elections', 'activeStudents'));
}




   public function addOfficer(Request $request, Election $election)
{
    $validated = $request->validate([
        'student_id' => ['required', 'exists:students,id'],
        'is_active'  => ['nullable', 'boolean'],
    ]);

    // prevent duplicates
    $exists = $election->generalOfficers()
        ->where('students.id', $validated['student_id'])
        ->exists();

    if ($exists) {
        return back()->with('error', 'This student is already assigned as an officer for this election.');
    }

    $election->generalOfficers()->attach($validated['student_id'], [
        'is_active' => $request->boolean('is_active', true),
    ]);

    return back()->with('success', 'General Election Officer added successfully.');
}

public function updateOfficer(Request $request, Election $election, Student $student)
{
    $validated = $request->validate([
        'student_id' => ['required', 'exists:students,id'],
        'is_active'  => ['nullable', 'boolean'],
    ]);

    // ensure the old officer is actually attached
    $attached = $election->generalOfficers()->where('students.id', $student->id)->exists();
    if (!$attached) {
        return back()->with('error', 'This student is not an officer for this election.');
    }

    // prevent duplicates: if new student already officer, stop
    $alreadyOfficer = $election->generalOfficers()
        ->where('students.id', $validated['student_id'])
        ->exists();

    if ($alreadyOfficer && (int)$validated['student_id'] !== (int)$student->id) {
        return back()->with('error', 'Selected student is already an officer for this election.');
    }

    // If student changed -> detach old and attach new with pivot status
    if ((int)$validated['student_id'] !== (int)$student->id) {
        $election->generalOfficers()->detach($student->id);

        $election->generalOfficers()->attach($validated['student_id'], [
            'is_active' => $request->boolean('is_active', true),
        ]);
    } else {
        // same student: update pivot status only
        $election->generalOfficers()->updateExistingPivot($student->id, [
            'is_active' => $request->boolean('is_active', true),
        ]);
    }

    return back()->with('success', 'Officer updated successfully.');
}


    public function create()
    {
        return view('elections.create');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'title'      => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date'   => 'required|date|after_or_equal:start_date',
        'open_time'  => 'required|date_format:H:i',
        'close_time' => 'required|date_format:H:i',
    ]);

    // Combine date + time to validate real window
    $openAt  = Carbon::parse($validated['start_date'].' '.$validated['open_time']);
    $closeAt = Carbon::parse($validated['end_date'].' '.$validated['close_time']);

    if ($closeAt->lessThanOrEqualTo($openAt)) {
        return back()->withErrors([
            'close_time' => 'Close time must be after open time (consider end date).'
        ])->withInput();
    }

    Election::create([
        'title'      => $validated['title'],
        'start_date' => $validated['start_date'],
        'end_date'   => $validated['end_date'],
        'open_time'  => $validated['open_time'],
        'close_time' => $validated['close_time'],
        'status'     => 'draft',
        'is_active'  => true,
    ]);

    return redirect()->route('elections.index')->with('success', 'Election created successfully');
}

    public function show(Election $election)
{
    $election->load([
        'positions.definition',
        'generalOfficers' => function ($q) {
            $q->orderBy('first_name')->orderBy('last_name');
        }
    ]);

    return view('elections.show', compact('election'));
}


    public function edit(Election $election)
    {
        abort_if($election->status !== 'draft', 403);
        return view('elections.edit', compact('election'));
    }

    public function update(Request $request, Election $election)
{
    abort_if($election->status !== 'draft', 403);

    $validated = $request->validate([
        'title'      => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date'   => 'required|date|after_or_equal:start_date',
        'open_time'  => 'required|date_format:H:i',
        'close_time' => 'required|date_format:H:i',
        'is_active'  => 'nullable|boolean',
    ]);

    // Build real datetimes for validation (date + time)
    $openAt  = Carbon::parse($validated['start_date'] . ' ' . $validated['open_time']);
    $closeAt = Carbon::parse($validated['end_date']   . ' ' . $validated['close_time']);

    // Ensure the voting window is valid
    if ($closeAt->lessThanOrEqualTo($openAt)) {
        return back()
            ->withErrors(['close_time' => 'Close time must be after open time (consider end date).'])
            ->withInput();
    }

    $election->update([
        'title'      => $validated['title'],
        'start_date' => $validated['start_date'],
        'end_date'   => $validated['end_date'],
        'open_time'  => $validated['open_time'],
        'close_time' => $validated['close_time'],
        'is_active'  => $request->boolean('is_active'), // checkbox-safe
    ]);

    return redirect()
        ->route('elections.index') // because your show route isn't used in the modal flow
        ->with('success', 'Election updated successfully');
}

    public function destroy(Election $election)
    {
        abort_if($election->status !== 'draft', 403);

        $election->delete();

        return redirect()
            ->route('elections.index')
            ->with('success', 'Election deleted successfully');
    }

    public function open(Election $election)
    {
        $election->update([
            'status' => 'open',
            'is_active' => true,
        ]);

        return back()->with('success', 'Election opened');
    }

    public function close(Election $election)
    {
        $election->update([
            'status' => 'closed',
            'is_active' => false,
        ]);

        return back()->with('success', 'Election closed');
    }
}
