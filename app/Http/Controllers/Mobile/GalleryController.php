<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
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
}