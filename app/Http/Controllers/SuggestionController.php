<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Notifications\NewMessageNotification;

class SuggestionController extends Controller
{
    public function store(Request $request)
    {
        try {
            Log::info('Starting suggestion store', ['input' => $request->all()]);

            $request->validate([
                'message' => 'required|string|max:1000',
                'is_anonymous' => 'required|boolean',
                'password' => 'required|string',
            ]);

            $student = $request->user();
            if (!$student) {
                Log::error('No authenticated user found');
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            if (!Hash::check($request->password, $student->password)) {
                Log::warning("Invalid password attempt for student {$student->id} ({$student->email})");
                return response()->json(['success' => false, 'message' => 'Invalid password'], 403);
            }

            $suggestion = Suggestion::create([
                'student_id' => $request->is_anonymous ? null : $student->id,
                'user_id' => null,
                'sender_type' => 'student',
                'message' => $request->message,
                'is_anonymous' => $request->is_anonymous,
                'status' => 'Received',
            ]);

            Log::info("Suggestion #{$suggestion->id} created by student {$student->id}");

            $admins = \App\Models\User::role('admin')->get();

            foreach ($admins as $admin) {
                try {
                    $admin->notify(new NewMessageNotification($suggestion));
                    Log::info("Notification sent to admin {$admin->id} ({$admin->email})");
                } catch (\Exception $e) {
                    Log::error("Failed to notify admin {$admin->id}: {$e->getMessage()}");
                }
            }

            Log::info("Notified {$admins->count()} admins for suggestion #{$suggestion->id}");

            return response()->json(['success' => true, 'suggestion' => $suggestion]);
        } catch (\Exception $e) {
            Log::error('Suggestion store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $student = $request->user();
            if (!$student) {
                Log::error('No authenticated user found');
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $suggestions = Suggestion::where(function ($query) use ($student) {
                $query->where('student_id', $student->id)
                    ->orWhereNull('student_id')
                    ->orWhere('sender_type', 'admin');
            })
                ->with(['user' => function ($query) {
                    $query->select('id', 'name', 'email');
                }])
                ->latest()
                ->get();

            Log::info("Fetched {$suggestions->count()} messages for student {$student->id} ({$student->email})");

            return response()->json(['suggestions' => $suggestions]);
        } catch (\Exception $e) {
            Log::error("Error fetching messages: {$e->getMessage()}");
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function verifyPassword(Request $request)
    {
        try {
            $request->validate(['password' => 'required|string']);
            $student = $request->user();
            if (!$student) {
                Log::error('No authenticated user found');
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            if (!Hash::check($request->password, $student->password)) {
                Log::warning("Invalid password attempt for student {$student->id} ({$student->email})");
                return response()->json(['success' => false, 'message' => 'Invalid password'], 403);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Password verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}