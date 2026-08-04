<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(Request $request): View
    {
        $query = AcademicYear::query()->orderByDesc('start_date');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $academicYears = $query->paginate(15)->withQueryString();
        $currentAcademicYear = AcademicYear::getCurrent();

        return view('academic-years.index', compact(
            'academicYears',
            'currentAcademicYear'
        ));
    }

    public function create(): View
    {
        return view('academic-years.create');
    }

    public function store(AcademicYearRequest $request): RedirectResponse
    {
        $academicYear = AcademicYear::create($request->validated());

        if ($academicYear->status === 'active') {
            $academicYear->activate();
        }

        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Academic year created successfully.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        return view('academic-years.edit', compact('academicYear'));
    }

    public function update(
        AcademicYearRequest $request,
        AcademicYear $academicYear
    ): RedirectResponse {
        $academicYear->update($request->validated());

        if ($academicYear->status === 'active') {
            $academicYear->activate();
        } elseif ($academicYear->activated_at !== null) {
            $academicYear->update(['activated_at' => null]);
        }

        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Academic year updated successfully.');
    }

    public function activate(AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->activate();

        return back()->with('success', "{$academicYear->name} is now the active academic year.");
    }

    public function archive(AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->update([
            'status' => 'archived',
            'activated_at' => null,
        ]);

        return back()->with('success', "{$academicYear->name} has been archived.");
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->status === 'active') {
            return back()->withErrors([
                'academic_year' => 'The active academic year cannot be deleted.',
            ]);
        }

        if (
            $academicYear->almanacSetups()->exists()
            || $academicYear->timetableSemesters()->exists()
            || $academicYear->examSetups()->exists()
        ) {
            return back()->withErrors([
                'academic_year' => 'This academic year is already in use and cannot be deleted.',
            ]);
        }

        $academicYear->delete();

        return back()->with('success', 'Academic year deleted successfully.');
    }
}
