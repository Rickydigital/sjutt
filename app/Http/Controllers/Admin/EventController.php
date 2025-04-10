<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $events = Event::when($search, function ($query, $search) {
            return $query->where('title', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('event_time', 'like', "%{$search}%");
        })->paginate(10);

        return view('admin.events.index', compact('events'));
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        return view('admin.events.create');
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'location' => 'required',
            'event_time' => 'required|date',
            'media' => 'nullable|image|mimes:jpeg,png,jpg,mp4,avi,mov',
        ]);

        $event = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'event_time' => $request->event_time,
            'user_allowed' => $request->user_allowed ?? true,
            'media' => $request->file('media') ? $request->file('media')->store('event_media', 'public') : null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('events.index')->with('success', 'Event added successfully');
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event)
    {
        return view('admin.events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event)
    {
        return view('admin.events.edit', compact('event'));
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'location' => 'required',
            'event_time' => 'required|date',
            'media' => 'nullable|image|mimes:jpeg,png,jpg,mp4,avi,mov',
        ]);

        if ($request->hasFile('media')) {
            if ($event->media) Storage::disk('public')->delete($event->media);
            $event->media = $request->file('media')->store('event_media', 'public');
        }

        $event->update([
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'event_time' => $request->event_time,
            'user_allowed' => $request->user_allowed ?? true,
        ]);

        return redirect()->route('events.index')->with('success', 'Event updated successfully');
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event)
    {
        if ($event->media) Storage::disk('public')->delete($event->media);
        $event->delete();
        return redirect()->route('events.index')->with('success', 'Event deleted successfully');
    }
}