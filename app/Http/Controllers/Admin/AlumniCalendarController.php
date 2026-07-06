<?php

namespace App\Http\Controllers\Admin;

use App\Models\AlumniCalendar;
use App\Models\AlumniEvent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AlumniCalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:manage alumni']);
    }

    public function index(Request $request)
    {
        $query = AlumniCalendar::with('event')->orderBy('calendar_date');
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        $calendars = $query->paginate(25)->withQueryString();
        $events = AlumniEvent::orderBy('title')->get();
        return view('alumni.calendars.index', compact('calendars', 'events'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'calendar_date' => ['required','date'],
            'start_time' => ['nullable','date_format:H:i'],
            'end_time' => ['nullable','date_format:H:i','after_or_equal:start_time'],
            'venue' => ['nullable','string','max:255'],
            'type' => ['required','in:meeting,assembly,convocation,deadline,event,other'],
            'status' => ['required','in:draft,published,cancelled'],
            'is_public' => ['nullable','boolean'],
            'alumni_event_id' => ['nullable','exists:alumni_events,id'],
        ]);
        $data['created_by'] = auth()->id();
        $data['is_public'] = $request->boolean('is_public', true);
        AlumniCalendar::create($data);
        return back()->with('success', 'Alumni calendar item created successfully.');
    }

    public function update(Request $request, AlumniCalendar $alumniCalendar)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'calendar_date' => ['required','date'],
            'start_time' => ['nullable','date_format:H:i'],
            'end_time' => ['nullable','date_format:H:i','after_or_equal:start_time'],
            'venue' => ['nullable','string','max:255'],
            'type' => ['required','in:meeting,assembly,convocation,deadline,event,other'],
            'status' => ['required','in:draft,published,cancelled'],
            'is_public' => ['nullable','boolean'],
            'alumni_event_id' => ['nullable','exists:alumni_events,id'],
        ]);
        $data['is_public'] = $request->boolean('is_public', true);
        $alumniCalendar->update($data);
        return back()->with('success', 'Alumni calendar item updated successfully.');
    }

    public function destroy(AlumniCalendar $alumniCalendar)
    {
        $alumniCalendar->delete();
        return back()->with('success', 'Alumni calendar item deleted successfully.');
    }
}
