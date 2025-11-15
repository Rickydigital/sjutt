<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index()
    {
        try {
            $events = Event::latest()->get();
            $events = $events->map(function ($item) {
                if ($item->media) {
                    $item->media = asset('storage/' . ltrim($item->media, '/')); // Full URL
                }
                return $item;
            });
            return response()->json([
                'success' => true,
                'message' => 'Events fetched successfully',
                'data' => $events
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to fetch events: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch events',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function latest()
    {
        try {
            $now = Carbon::now('UTC'); // Use UTC to match database timezone
            $events = Event::where('event_time', '>=', $now)
                ->latest()
                ->take(5)
                ->get();
            $events = $events->map(function ($item) {
                if ($item->media) {
                    $item->media = asset('storage/' . ltrim($item->media, '/')); // Full URL
                }
                return $item;
            });
            return response()->json([
                'success' => true,
                'message' => 'Latest events fetched successfully',
                'data' => $events
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to fetch latest events: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch latest events',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getEvents(): JsonResponse
    {
        $now = Carbon::now();

        $events = Event::with('user')
            ->where('end_time', '>', $now)
            ->select('id', 'title', 'description', 'location', 'start_time', 'end_time', 'user_allowed', 'media', 'created_by', 'created_at', 'updated_at')
            ->orderBy('start_time', 'asc')
            ->get();

        $data = $events->map(function ($event) {
            $mediaUrl = null;
            $mediaType = null;
            if ($event->media) {
                $mediaType = $this->getMediaType($event->media);
                if ($mediaType === 'video') {
                    // Assumes event videos are in 'public/events_media'
                    $mediaUrl = 'events_media/' . basename($event->media);
                } else {
                    $mediaUrl = $event->media; // Return relative path
                }
            }

            return [
                'id'            => $event->id,
                'title'         => $event->title,
                'description'   => $event->description,
                'location'      => $event->location,
                'start_time'    => $event->start_time->toISOString(),
                'end_time'      => $event->end_time->toISOString(),
                'user_allowed'  => $event->user_allowed,
                'media'         => $mediaUrl,
                'media_type'    => $mediaType,
                'created_at'    => $event->created_at->toISOString(),
                'updated_at'    => $event->updated_at->toISOString(),
                'creator'       => $event->user ? $event->user->only('id', 'name', 'email', 'phone', 'status') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data->values()->toArray(),
        ]);
    }

    private function getMediaType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'mkv'];
        return in_array($extension, $videoExtensions) ? 'video' : 'image';
    }
    
}