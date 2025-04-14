<?php



namespace App\Http\Controllers;

use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSuggestionController extends Controller
{
    public function index()
    {
        $suggestions = Suggestion::with(['student', 'user'])->latest()->paginate(10);
        return view('admin.suggestions.index', compact('suggestions'));
    }

    public function show(Suggestion $suggestion)
    {
        if ($suggestion->status === 'Received') {
            $suggestion->update(['status' => 'Viewed']);
        }
        return view('admin.suggestions.show', compact('suggestion'));
    }

    public function reply(Request $request, Suggestion $suggestion)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $admin = $request->user();

        $reply = Suggestion::create([
            'student_id' => $suggestion->student_id,
            'user_id' => $admin->id,
            'sender_type' => 'admin',
            'message' => $request->message,
            'is_anonymous' => $suggestion->is_anonymous,
            'status' => 'Processed',
        ]);

        Log::info("Reply #{$reply->id} sent by admin {$admin->id}");

        return redirect()->route('admin.suggestions.index')->with('success', 'Reply sent');
    }
}