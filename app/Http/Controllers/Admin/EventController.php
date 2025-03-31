<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
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

        return response()->json($event, 201);
    }

    public function destroy(Event $event)
    {
        if ($event->media) Storage::disk('public')->delete($event->media);
        $event->delete();
        return response()->json(['message' => 'Event deleted']);
    }
}
