<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminSuggestionController extends Controller
{
   public function index()
{
    $search = request('search');
    $status = request('status');

    $conversations = Suggestion::select('student_id', DB::raw('MAX(created_at) as last_message_at'))
        ->whereNotNull('student_id')
        ->when($search, function ($q, $s) {
            return $q->whereHas('student', fn($sq) => $sq->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('reg_no', 'like', "%{$s}%"));
        })
        ->when($status, function ($q, $s) {
            return $q->whereHas('suggestions', fn($sq) => $sq->where('status', $s));
        })
        ->groupBy('student_id')
        ->with(['student'])
        ->orderBy('last_message_at', 'desc')
        ->paginate(10);

    $conversations->getCollection()->transform(function ($conv) {
        $conv->last_message_at = Carbon::parse($conv->last_message_at);
        return $conv;
    });

    return view('admin.suggestions.index', compact('conversations'));
}

    public function conversation($student_id)
    {
        try {
            $student = Student::findOrFail($student_id);
            $suggestions = Suggestion::where('student_id', $student_id)
                ->with([
                    'student' => function ($query) {
                        $query->select('id', 'name', 'email');
                    },
                    'user' => function ($query) {
                        $query->select('id', 'name', 'email');
                    }
                ])
                ->orderBy('created_at', 'asc')
                ->get();
    
            // Log suggestions for debugging
            Log::debug("Suggestions for student_id {$student_id}", [
                'count' => $suggestions->count(),
                'suggestions' => $suggestions->map(function ($suggestion) {
                    return [
                        'id' => $suggestion->id,
                        'sender_type' => $suggestion->sender_type,
                        'student_id' => $suggestion->student_id,
                        'user_id' => $suggestion->user_id,
                        'message' => $suggestion->message,
                    ];
                })->toArray(),
            ]);
    
            // Update status to 'Viewed' for student messages
            Suggestion::where('student_id', $student_id)
                ->where('sender_type', 'student')
                ->where('status', 'Received')
                ->update(['status' => 'Viewed']);
    
            $hasNonAnonymous = Suggestion::where('student_id', $student_id)
                ->where('sender_type', 'student')
                ->where('is_anonymous', false)
                ->exists();
            $conversationTitle = $hasNonAnonymous ? "Conversation with {$student->name}" : "Anonymous Conversation";
    
            return view('admin.suggestions.conversation', compact('suggestions', 'student', 'conversationTitle'));
        } catch (\Exception $e) {
            Log::error("Error loading conversation for student_id {$student_id}: {$e->getMessage()}");
            return redirect()->route('admin.suggestions.index')->with('error', 'Unable to load conversation.');
        }
    }

    public function replyToStudent(Request $request, $student_id)
    {
        try {
            $request->validate(['message' => 'required|string|max:1000']);
            $admin = $request->user();

            if (!$admin) {
                Log::error('No authenticated admin found');
                return redirect()->route('admin.suggestions.conversation', $student_id)
                    ->with('error', 'Unauthorized');
            }

            $suggestion = Suggestion::create([
                'student_id' => $student_id,
                'user_id' => $admin->id,
                'sender_type' => 'admin',
                'message' => $request->message,
                'is_anonymous' => false,
                'status' => 'Processed',
            ]);

            Log::info("Reply #{$suggestion->id} sent by admin {$admin->id} to student {$student_id}");
            return redirect()->route('admin.suggestions.conversation', $student_id)
                ->with('success', 'Reply sent');
        } catch (\Exception $e) {
            Log::error("Error sending reply to student {$student_id}: {$e->getMessage()}");
            return redirect()->route('admin.suggestions.conversation', $student_id)
                ->with('error', 'Failed to send reply.');
        }
    }
}