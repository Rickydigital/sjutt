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
        if (!Auth::check()) {
            return redirect()->route('events.index')->with('error', 'You must be logged in.');
        }

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

        try {
            // Handle multiple venues or custom location
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

            $mediaPath = $request->hasFile('media')
                ? $request->file('media')->store('event_media', 'public')
                : null;

            $event = Event::create([
                'title'        => $request->title,
                'description'  => $request->description,
                'location'     => $locations, // ← Stored as JSON array automatically
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'user_allowed' => $request->access,
                'media'        => $mediaPath,
                'created_by'   => Auth::id(),
            ]);

            SendEventNotificationJob::dispatchAfterResponse(
                $event,
                $request->access,
                $mediaPath ? Storage::url($mediaPath) : null
            );

            return redirect()->route('events.index')
                ->with('success', 'Event created successfully!');
        } catch (\Exception $e) {
            Log::error('Event creation failed: ' . $e->getMessage(), $request->all());
            return back()->withInput()->with('error', 'Failed to create event: ' . $e->getMessage());
        }
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
    private function sendEventNotification(Event $event, array $access, ?string $mediaPath): void
    {
        $title = $event->title;
        $body  = Str::limit(strip_tags($event->description), 100);
        $image = $mediaPath ? Storage::url($mediaPath) : null;

        $query = Student::whereNotNull('fcm_token');

        if (!in_array('all', $access)) {
            $allowedRoles = [];
            if (in_array('student', $access)) $allowedRoles[] = 'student';
            if (in_array('staff', $access))   $allowedRoles[] = 'staff';

            $query->whereHas('roles', fn($q) => $q->whereIn('name', $allowedRoles));
        }

        $query->select('id', 'fcm_token')
              ->chunk(500, function ($students) use ($title, $body, $image) {
                  $tokens = $students->pluck('fcm_token')
                      ->filter(fn($t) => is_string($t) && strlen($t) > 50)
                      ->unique()
                      ->values()
                      ->all();

                  if (empty($tokens)) return;

                  $this->sendFcmNotification($tokens, $title, $body, $image);
              });
    }

    private function sendFcmNotification(array $tokens, string $title, string $body, ?string $image = null): void
    {
        $credentials = config('firebase.credentials');
        if (!$credentials || !file_exists($credentials)) {
            Log::error("Firebase credentials missing or invalid: " . ($credentials ?? 'null'));
            return;
        }

        try {
            $factory = (new Factory)->withServiceAccount($credentials);
            $messaging = $factory->createMessaging();

            $message = CloudMessage::new()
                ->withNotification([
                    'title' => $title,
                    'body'  => $body,
                    'image' => $image,
                ])
                ->withData([
                    'type' => 'event',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            $report = $messaging->sendMulticast($message, $tokens);

            $success = $report->successes()->count();
            $failed  = $report->failures()->count();

            Log::info("Event FCM sent: {$success} success, {$failed} failed", [
                'title' => $title,
                'token_count' => count($tokens),
            ]);

            $invalidTokens = [];
            foreach ($report->failures() as $failure) {
                $token = $failure->target()->value();
                $error = $failure->error();

                if ($token && in_array($error?->getReason(), ['UNREGISTERED', 'INVALID_REGISTRATION', 'NOT_FOUND'])) {
                    $invalidTokens[] = $token;
                }
            }

            if (!empty($invalidTokens)) {
                Student::whereIn('fcm_token', $invalidTokens)->update(['fcm_token' => null]);
            }
        } catch (\Throwable $e) {
            Log::error("Event FCM send failed: " . $e->getMessage(), ['title' => $title]);
        }
    }
}