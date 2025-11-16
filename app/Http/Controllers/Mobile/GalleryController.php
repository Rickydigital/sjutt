<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $galleries = Gallery::when($search, function ($query, $search) {
            return $query->where('description', 'like', "%{$search}%");
        })->paginate(10);

        // Transform media URLs to absolute paths
        $galleries->getCollection()->transform(function ($gallery) {
            $gallery->media = array_map(function ($path) {
                // Remove leading '/storage/' to get clean path (e.g., 'gallery/image.png')
                $cleanPath = ltrim(str_replace('/storage/', '', $path), '/');
                // Generate absolute URL
                return Storage::url($cleanPath);
            }, $gallery->media);
            return $gallery;
        });

        return response()->json([
            'success' => true,
            'data' => $galleries->items(),
            'pagination' => [
                'current_page' => $galleries->currentPage(),
                'last_page' => $galleries->lastPage(),
                'per_page' => $galleries->perPage(),
                'total' => $galleries->total(),
            ],
        ], 200);
    }

    public function latest(Request $request)
    {
        $galleries = Gallery::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Transform media URLs to absolute paths
        $galleries->transform(function ($gallery) {
            $gallery->media = array_map(function ($path) {
                // Remove leading '/storage/' to get clean path (e.g., 'gallery/image.png')
                $cleanPath = ltrim(str_replace('/storage/', '', $path), '/');
                // Generate absolute URL
                return Storage::url($cleanPath);
            }, $gallery->media);
            return $gallery;
        });

        return response()->json([
            'success' => true,
            'data' => $galleries,
        ], 200);
    }

    public function getGallery(): JsonResponse
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
                'media'        => $media, // Assumes full URLs are stored in DB
                'likes_count'  => $likeCount,
                'creator'      => $gallery->user ? $gallery->user->only('id', 'name', 'email', 'phone', 'status') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data->values()->toArray(),
        ]);
    }
}