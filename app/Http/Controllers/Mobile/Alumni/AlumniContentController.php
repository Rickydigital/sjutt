<?php

namespace App\Http\Controllers\Mobile\Alumni;

use App\Http\Controllers\Controller;
use App\Models\AlumniCalendar;
use App\Models\AlumniEvent;
use App\Models\AlumniPost;
use Illuminate\Http\Request;

class AlumniContentController extends Controller
{
    public function events(Request $request)
    {
        $events = AlumniEvent::query()
            ->where('status', 'published')
            ->orderBy('starts_at')
            ->paginate(20);

        return response()->json(['status' => 'success', 'data' => $events]);
    }

    public function eventShow(AlumniEvent $event)
    {
        abort_if($event->status !== 'published', 404);
        return response()->json(['status' => 'success', 'data' => $event]);
    }

    public function calendars(Request $request)
    {
        $items = AlumniCalendar::with('event')
            ->where('is_public', true)
            ->where('status', 'published')
            ->orderBy('calendar_date')
            ->paginate(30);

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function posts(Request $request)
    {
        $posts = AlumniPost::query()
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate(20);

        return response()->json(['status' => 'success', 'data' => $posts]);
    }

    public function postShow(AlumniPost $post)
    {
        abort_if($post->status !== 'published', 404);
        return response()->json(['status' => 'success', 'data' => $post]);
    }

    public function submitPost(Request $request)
    {
        $alumni = $request->user();

        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'body' => ['required','string'],
            'image' => ['nullable','image','max:4096'],
            'category' => ['required','in:announcement,news,opportunity,story,general'],
        ]);

        if ($request->hasFile('image')) $data['image'] = $request->file('image')->store('alumni/posts', 'public');
        $data['status'] = 'pending';
        $data['postable_type'] = get_class($alumni);
        $data['postable_id'] = $alumni->id;

        $post = AlumniPost::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Post submitted for approval.',
            'data' => $post,
        ], 201);
    }
}
