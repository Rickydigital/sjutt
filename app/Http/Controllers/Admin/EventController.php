<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendEventNotificationJob;
use App\Models\Event;
use App\Models\Venue;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $filterCreator = $request->query('filter_creator');

        $events = Event::with('user')
            ->when($search, function ($q, $s) {
                return $q->where('title', 'like', "%{$s}%")
                         ->orWhereJsonContains('location', $s); // Works with JSON array
            })
            ->when($filterCreator, fn($q, $id) => $q->where('created_by', $id))
            ->latest()
            ->paginate(10);

        return view('admin.events.index', compact('events'));
    }

    public function store(Request $request)
{
    $request->validate([
        'title'           => 'required|string|max:255',
        'description'     => 'required|string',
        'location_type'   => 'required|in:venue,custom',
        'venue_ids'       => 'required_if:location_type,venue|array|min:1',
        'venue_ids.*'     => 'exists:venues,id',
        'custom_location' => 'required_if:location_type,custom|string|max:255',
        'start_time'      => 'required|date|after:now',
        'end_time'        => 'required|date|after:start_time',
        'media'           => 'nullable|file|mimes:jpeg,png,jpg,mp4,avi,mov|max:20480',
        'access'          => 'required|array|min:1',
        'access.*'        => 'in:all,staff,student',
    ]);

    $locations = [];

    if ($request->location_type === 'venue' && $request->venue_ids) {
        $locations = Venue::whereIn('id', $request->venue_ids)->pluck('name')->toArray();
    } elseif ($request->location_type === 'custom') {
        $locations = [$request->custom_location];
    }

    if (empty($locations)) {
        return back()->withInput()->with('error', 'Please select a location.');
    }

    $mediaPath = $request->hasFile('media')
        ? $request->file('media')->store('event_media', 'public')
        : null;

    $event = Event::create([
        'title'        => $request->title,
        'description'  => $request->description,
        'location'     => $locations,
        'start_time'   => $request->start_time,
        'end_time'     => $request->end_time,
        'user_allowed' => $request->access,
        'media'        => $mediaPath,
        'created_by'   => Auth::id(),
    ]);

    $imageUrl = $mediaPath ? asset('storage/' . $mediaPath) : null;

    // MAGIC: Sends AFTER response — admin sees redirect instantly
    SendEventNotificationJob::dispatch($event, $request->access, $imageUrl)->afterResponse();

    return redirect()->route('events.index')
        ->with('success', 'Event created successfully! Students are being notified...');
}

    public function update(Request $request, Event $event)
    {
        if (Auth::id() !== $event->created_by && !Auth::user()->hasRole('Admin')) {
            return back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'required|string',
            'location_type'   => 'required|in:venue,custom',
            'venue_ids'       => 'required_if:location_type,venue|array|min:1',
            'venue_ids.*'     => 'exists:venues,id',
            'custom_location' => 'required_if:location_type,custom|string|max:255',
            'start_time'      => 'required|date',
            'end_time'        => 'required|date|after:start_time',
            'media'           => 'nullable|file|mimes:jpeg,png,jpg,mp4,avi,mov|max:20480',
            'access'          => 'required|array|min:1',
            'access.*'        => 'in:all,staff,student',
        ]);

        try {
            $locations = [];

            if ($request->location_type === 'venue' && $request->venue_ids) {
                $locations = Venue::whereIn('id', $request->venue_ids)
                    ->pluck('name')
                    ->toArray();
            } elseif ($request->location_type === 'custom') {
                $locations = [$request->custom_location];
            }

            if (empty($locations)) {
                return back()->withInput()->with('error', 'Please select at least one venue or enter a custom location.');
            }

            if ($request->hasFile('media')) {
                if ($event->media) {
                    Storage::disk('public')->delete($event->media);
                }
                $event->media = $request->file('media')->store('event_media', 'public');
            }

            $event->update([
                'title'        => $request->title,
                'description'  => $request->description,
                'location'     => $locations, // ← JSON array
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'user_allowed' => $request->access,
            ]);

            return redirect()->route('events.index')
                ->with('success', 'Event updated successfully.');
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

        if ($event->media) {
            Storage::disk('public')->delete($event->media);
        }
        $event->delete();

        return redirect()->route('events.index')
            ->with('success', 'Event deleted.');
    }

    // ──────────────────────────────────────────────────────────────
    // SEND PUSH NOTIFICATION (unchanged)
    // ──────────────────────────────────────────────────────────────

}