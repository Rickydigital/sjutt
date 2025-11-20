<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use Illuminate\Http\Request;

class AdminSuggestionController extends Controller
{
    public function index()
    {
        // Stats
        $total = Suggestion::count();
        $received = Suggestion::where('status', 'Received')->count();
        $viewed = Suggestion::where('status', 'Viewed')->count();
        $processed = Suggestion::where('status', 'Processed')->count();

        // Custom sorting: Received first, then Viewed, then Processed → latest first
        $suggestions = Suggestion::query()
            ->selectRaw('*, 
                CASE 
                    WHEN status = "Received" THEN 1
                    WHEN status = "Viewed" THEN 2
                    WHEN status = "Processed" THEN 3
                    ELSE 4 
                END as status_order')
            ->orderBy('status_order')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.suggestions.index', compact(
            'suggestions', 'total', 'received', 'viewed', 'processed'
        ));
    }

    public function markViewed($id)
    {
        $suggestion = Suggestion::findOrFail($id);
        if ($suggestion->status === 'Received') {
            $suggestion->update(['status' => 'Viewed']);
        }
        return response()->json(['success' => true]);
    }

    public function markProcessed($id)
    {
        $suggestion = Suggestion::findOrFail($id);
        if ($suggestion->status !== 'Processed') {
            $suggestion->update(['status' => 'Processed']);
        }
        return response()->json(['success' => true]);
    }

    public function getMessage($id)
    {
        $msg = Suggestion::findOrFail($id);
        return response()->json([
            'id' => $msg->id,
            'message' => $msg->message,
            'status' => $msg->status,
            'created_at' => $msg->created_at->format('d M Y, h:i A'),
            'is_anonymous' => $msg->is_anonymous
        ]);
    }
}