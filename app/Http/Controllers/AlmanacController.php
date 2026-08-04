<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AlmanacSetup;
use App\Models\Program;
use App\Services\AlmanacCalendarService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AlmanacController extends Controller
{
    public function index(Request $request, AlmanacCalendarService $calendarService)
    {
        $setups = AlmanacSetup::with('academicYear')->latest()->get();
        $setup = $request->filled('setup_id')
            ? AlmanacSetup::find($request->integer('setup_id'))
            : AlmanacSetup::getCurrent() ?? $setups->first();

        $calendar = $setup ? $calendarService->build($setup) : null;

        return view('almanac.index', [
            'setups' => $setups,
            'setup' => $setup,
            'calendar' => $calendar,
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'programs' => Program::orderBy('name')->get(),
        ]);
    }

    public function exportPdf(AlmanacSetup $setup, AlmanacCalendarService $calendarService)
    {
        $calendar = $calendarService->build($setup);

        return Pdf::loadView('almanac.pdf.almanac', compact('setup', 'calendar'))
            ->setPaper('a4', 'landscape')
            ->download('academic-almanac-' . str_replace('/', '-', $setup->academicYear?->year ?? $setup->id) . '.pdf');
    }
}
