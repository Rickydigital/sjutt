<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\AlmanacSetup;
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
        $setup = AlmanacSetup::getCurrent();

        if (!$setup) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $events = $setup->events()
            ->with('creator')
            ->whereDate('end_date', '>=', today())
            ->orWhere(function ($query) use ($setup) {
                $query->where('almanac_setup_id', $setup->id)
                    ->whereNull('end_date')
                    ->whereDate('start_date', '>=', today());
            })
            ->orderBy('start_date')
            ->get();

        $data = $events->map(function ($event) {
            $start = $event->start_date->copy()->startOfDay();
            $end = ($event->end_date ?? $event->start_date)->copy()->endOfDay();

            return [
                'id'            => $event->id,
                'title'         => $event->title,
                'description'   => $event->description,
                'location'      => null,
                'start_time'    => $start->toISOString(),
                'end_time'      => $end->toISOString(),
                'user_allowed'  => $event->applies_to_all ? 'All' : 'Selected Programs',
                'media'         => null,
                'media_type'    => null,
                'created_at'    => $event->created_at->toISOString(),
                'updated_at'    => $event->updated_at->toISOString(),
                'creator'       => $event->creator ? $event->creator->only('id', 'name', 'email', 'phone', 'status') : null,
                'category'      => $event->category,
                'event_column'  => $event->event_column,
                'is_no_classes' => $event->is_no_classes,
                'is_tentative'  => $event->is_tentative,
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
