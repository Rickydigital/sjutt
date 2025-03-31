<?php

namespace App\Http\Controllers;

use App\Models\Reaction;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'news_id' => 'required|exists:news,id',
            'type' => 'required|in:like,dislike',
        ]);

        $reaction = Reaction::updateOrCreate(
            ['news_id' => $request->news_id, 'user_id' => auth()->id()],
            ['type' => $request->type]
        );

        return response()->json($reaction);
    }
}
