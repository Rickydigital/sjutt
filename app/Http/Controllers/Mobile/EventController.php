<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    
}