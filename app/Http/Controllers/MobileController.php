<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class MobileController extends Controller
{
   
    public function gallery(): JsonResponse
    {
        $galleries = Gallery::with(['user', 'likes'])
            ->select('id', 'description', 'media', 'created_by', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $galleries->map(function ($gallery) {
            $media = $gallery->media ?? []; // Already cast to array
            $likeCount = $gallery->likes->count();

            return [
                'id'           => $gallery->id,
                'description'  => $gallery->description,
                'created_at'   => $gallery->created_at->toISOString(),
                'updated_at'   => $gallery->updated_at->toISOString(),
                'creator_id'   => $gallery->created_by,
                'media_count'  => count($media),
                'media'        => $media, // Full URLs stored in DB
                'likes_count'  => $likeCount,
                'creator'      => [
                    'id'     => $gallery->user?->id,
                    'name'   => $gallery->user?->name ?? 'Unknown',
                    'email'  => $gallery->user?->email,
                    'phone'  => $gallery->user?->phone,
                    'status' => $gallery->user?->status ?? 'active',
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data->values()->toArray(),
        ]);
    }


public function events(): JsonResponse
{
    $now = Carbon::now(); 

    $events = Event::with('user')
        ->where('end_time', '>', $now) 
        ->select('id', 'title', 'description', 'location', 'start_time', 'end_time', 'user_allowed', 'media', 'created_by', 'created_at', 'updated_at')
        ->orderBy('start_time', 'asc') 
        ->get();

    $data = $events->map(function ($event) {
        $mediaUrl = $event->media ? asset('storage/' . $event->media) : null;

        return [
            'id'            => $event->id,
            'title'         => $event->title,
            'description'   => $event->description,
            'location'      => $event->location,
            'start_time'    => $event->start_time->toISOString(),
            'end_time'      => $event->end_time->toISOString(),
            'user_allowed'  => $event->user_allowed,
            'media'         => $mediaUrl,
            'media_type'    => $event->media ? $this->getMediaType($event->media) : null,
            'created_at'    => $event->created_at->toISOString(),
            'updated_at'    => $event->updated_at->toISOString(),
            'creator'       => [
                'id'     => $event->user?->id,
                'name'   => $event->user?->name ?? 'Unknown',
                'email'  => $event->user?->email,
                'phone'  => $event->user?->phone,
                'status' => $event->user?->status ?? 'active',
            ],
        ];
    });

    return response()->json([
        'success' => true,
        'data'    => $data->values()->toArray(),
    ]);
}

    /**
     * Detect if media is image or video
     */
    private function getMediaType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image' : 'video';
    }

    public function news(): JsonResponse
    {
        $newsItems = News::with(['user', 'reactions', 'comments'])
            ->select('id', 'title', 'description', 'image', 'video', 'created_by', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $newsItems->map(function ($news) {
            return [
                'id'           => $news->id,
                'title'        => $news->title,
                'description'  => $news->description,
                'image'        => $news->image ? asset('storage/' . $news->image) : null,
                'video'        => $news->video ? asset('storage/' . $news->video) : null,
                'created_at'   => $news->created_at->toISOString(),
                'updated_at'   => $news->updated_at->toISOString(),
                'reactions_count' => $news->reactions->count(),
                'comments_count'  => $news->comments->count(),
                'creator'      => [
                    'id'     => $news->user?->id,
                    'name'   => $news->user?->name ?? 'Unknown',
                    'email'  => $news->user?->email,
                    'phone'  => $news->user?->phone,
                    'status' => $news->user?->status ?? 'active',
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data->values()->toArray(),
        ]);
    }
}