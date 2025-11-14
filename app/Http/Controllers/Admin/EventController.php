<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $filterCreator = $request->query('filter_creator');

        $events = Event::with('user')
            ->when($search, fn($q, $s) => $q->where('title', 'like', "%{$s}%")
                ->orWhere('location', 'like', "%{$s}%"))
            ->when($filterCreator, fn($q, $id) => $q->where('created_by', $id))
            ->latest()
            ->paginate(10);

        return view('admin.events.index', compact('events'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('events.index')->with('error', 'You must be logged in.');
        }

        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'location_type'  => 'required|in:venue,custom',
            'venue_id'       => 'required_if:location_type,venue|exists:venues,id',
            'custom_location' => 'required_if:location_type,custom|string|max:255|nullable',            'start_time'     => 'required|date|after:now',
            'end_time'       => 'required|date|after:start_time',
            'media'          => 'nullable|file|mimes:jpeg,png,jpg,mp4,avi,mov|max:20480',
            'access'         => 'required|array|min:1',
            'access.*'       => 'in:all,staff,student',   
        ]);

        try {
            $location = $request->location_type === 'venue'
                ? Venue::findOrFail($request->venue_id)->name
                : $request->custom_location;

            $userAllowed = $request->access; // ["all"], ["staff","student"], etc.

            $mediaPath = $request->hasFile('media')
                ? $request->file('media')->store('event_media', 'public')
                : null;

            Event::create([
                'title'        => $request->title,
                'description'  => $request->description,
                'location'     => $location,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'user_allowed' => $userAllowed,
                'media'        => $mediaPath,
                'created_by'   => Auth::id(),
            ]);

            return redirect()->route('events.index')->with('success', 'Event created.');
        } catch (\Exception $e) {
            Log::error('Event creation failed: ' . $e->getMessage(), $request->all());
            return back()->withInput()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Event $event)
    {
        if (Auth::id() !== $event->created_by && !Auth::user()->hasRole('Admin')) {
            return back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'location_type'  => 'required|in:venue,custom',
            'venue_id'       => 'required_if:location_type,venue|exists:venues,id',
            'custom_location'=> 'required_if:location_type,custom|string|max:255',
            'start_time'     => 'required|date',
            'end_time'       => 'required|date|after:start_time',
            'media'          => 'nullable|file|mimes:jpeg,png,jpg,mp4,avi,mov|max:20480',
            'access'         => 'required|array|min:1',
            'access.*'       => 'in:all,staff,student',
        ]);

        try {
            $location = $request->location_type === 'venue'
                ? Venue::findOrFail($request->venue_id)->name
                : $request->custom_location;

            $userAllowed = $request->access;

            if ($request->hasFile('media')) {
                if ($event->media) Storage::disk('public')->delete($event->media);
                $event->media = $request->file('media')->store('event_media', 'public');
            }

            $event->update([
                'title'        => $request->title,
                'description'  => $request->description,
                'location'     => $location,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'user_allowed' => $userAllowed,
            ]);

            return redirect()->route('events.index')->with('success', 'Event updated.');
        } catch (\Exception $e) {
            Log::error('Event update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function destroy(Event $event)
    {
        if (Auth::id() !== $event->created_by && !Auth::user()->hasRole('Admin')) {
            return back()->with('error', 'Unauthorized.');
        }

        if ($event->media) Storage::disk('public')->delete($event->media);
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Event deleted.');
    }
}