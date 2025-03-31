<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\GalleryLike;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function store(Request $request)
    {
        $like = GalleryLike::create([
            'gallery_id' => $request->gallery_id,
            'user_id' => $request->user_id,
        ]);

        return response()->json($like);
    }

    public function destroy(Request $request)
    {
        $like = GalleryLike::where('gallery_id', $request->gallery_id)
                          ->where('user_id', $request->user_id)
                          ->first();

        if ($like) {
            $like->delete();
        }

        return response()->json(['message' => 'Like removed successfully.']);
    }
}
