<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Models\AlumniCalendar;
use App\Models\AlumniEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlumniEventController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:manage alumni']);
    }

    public function index(Request $request)
    {
        $query = AlumniEvent::query()->latest();
        if ($request->filled('search')) $query->where('title', 'like', '%'.$request->search.'%');
        if ($request->filled('status')) $query->where('status', $request->status);
        $events = $query->paginate(20)->withQueryString();
        return view('alumni.events.index', compact('events'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'venue' => ['nullable','string','max:255'],
            'location' => ['nullable','string','max:255'],
            'starts_at' => ['nullable','date'],
            'ends_at' => ['nullable','date','after_or_equal:starts_at'],
            'banner' => ['nullable','image','max:4096'],
            'status' => ['required','in:draft,published,cancelled,completed'],
            'requires_registration' => ['nullable','boolean'],
            'capacity' => ['nullable','integer','min:1'],
            'add_to_calendar' => ['nullable','boolean'],
        ]);

        if ($request->hasFile('banner')) $data['banner'] = $request->file('banner')->store('alumni/events', 'public');
        $data['created_by'] = auth()->id();
        $data['requires_registration'] = $request->boolean('requires_registration');
        if ($data['status'] === 'published') {
            $data['published_by'] = auth()->id();
            $data['published_at'] = now();
        }

        $event = AlumniEvent::create($data);

        if ($request->boolean('add_to_calendar') && $event->starts_at) {
            AlumniCalendar::create([
                'title' => $event->title,
                'description' => $event->description,
                'calendar_date' => $event->starts_at->toDateString(),
                'start_time' => $event->starts_at->format('H:i:s'),
                'end_time' => $event->ends_at?->format('H:i:s'),
                'venue' => $event->venue,
                'type' => 'event',
                'status' => $event->status === 'published' ? 'published' : 'draft',
                'is_public' => true,
                'alumni_event_id' => $event->id,
                'created_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Alumni event created successfully.');
    }

    public function update(Request $request, AlumniEvent $alumniEvent)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'venue' => ['nullable','string','max:255'],
            'location' => ['nullable','string','max:255'],
            'starts_at' => ['nullable','date'],
            'ends_at' => ['nullable','date','after_or_equal:starts_at'],
            'banner' => ['nullable','image','max:4096'],
            'status' => ['required','in:draft,published,cancelled,completed'],
            'requires_registration' => ['nullable','boolean'],
            'capacity' => ['nullable','integer','min:1'],
        ]);

        if ($request->hasFile('banner')) {
            if ($alumniEvent->banner) Storage::disk('public')->delete($alumniEvent->banner);
            $data['banner'] = $request->file('banner')->store('alumni/events', 'public');
        }
        $data['requires_registration'] = $request->boolean('requires_registration');
        if ($data['status'] === 'published' && !$alumniEvent->published_at) {
            $data['published_by'] = auth()->id();
            $data['published_at'] = now();
        }

        $alumniEvent->update($data);
        return back()->with('success', 'Alumni event updated successfully.');
    }

    public function destroy(AlumniEvent $alumniEvent)
    {
        if ($alumniEvent->banner) Storage::disk('public')->delete($alumniEvent->banner);
        $alumniEvent->delete();
        return back()->with('success', 'Alumni event deleted successfully.');
    }
}
