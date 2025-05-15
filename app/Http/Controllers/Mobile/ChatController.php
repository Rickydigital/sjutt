<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function getUsers(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $yearOfStudy = $request->query('year_of_study');

        if (!$facultyId || !$yearOfStudy) {
            return response()->json(['message' => 'Faculty ID and year of study are required'], 400);
        }

        $users = Student::where('faculty_id', $facultyId)
            ->where('year_of_study', $yearOfStudy)
            ->select('id', 'name', 'reg_no')
            ->get();

        return response()->json(['data' => $users], 200);
    }

    public function getMessages(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $yearOfStudy = $request->query('year_of_study');

        if (!$facultyId || !$yearOfStudy) {
            return response()->json(['message' => 'Faculty ID and year of study are required'], 400);
        }

        $messages = ChatMessage::where('faculty_id', $facultyId)
            ->where('year_of_study', $yearOfStudy)
            ->with(['sender:id,name', 'taggedUser:id,name'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender ? $message->sender->name : 'Unknown',
                    'content' => $message->content,
                    'tagged_user_id' => $message->tagged_user_id,
                    'created_at' => $message->created_at->toIso8601String(),
                    'is_deleted' => $message->is_deleted,
                ];
            });

        return response()->json(['data' => $messages], 200);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'year_of_study' => 'required|integer',
            'content' => 'required|string',
            'tagged_user_id' => 'nullable|exists:students,id',
        ]);

        $message = ChatMessage::create([
            'faculty_id' => $request->faculty_id,
            'year_of_study' => $request->year_of_study,
            'sender_id' => Auth::id(),
            'content' => $request->content,
            'tagged_user_id' => $request->tagged_user_id,
        ]);

        return response()->json([
            'data' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => Auth::user()->name,
                'content' => $message->content,
                'tagged_user_id' => $message->tagged_user_id,
                'created_at' => $message->created_at->toIso8601String(),
                'is_deleted' => $message->is_deleted,
            ]
        ], 201);
    }

    public function deleteMessage($id)
    {
        $message = ChatMessage::where('id', $id)
            ->where('sender_id', Auth::id())
            ->firstOrFail();

        $message->is_deleted = true;
        $message->content = "This message was deleted by {$message->sender->name}";
        $message->save();

        return response()->json([
            'data' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'content' => $message->content,
                'tagged_user_id' => $message->tagged_user_id,
                'created_at' => $message->created_at->toIso8601String(),
                'is_deleted' => $message->is_deleted,
            ]
        ], 200);
    }


    public function getStats(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $yearOfStudy = $request->query('year_of_study');

        if (!$facultyId || !$yearOfStudy) {
            return response()->json(['message' => 'Faculty ID and year of study are required'], 400);
        }

        $totalUsers = Student::where('faculty_id', $facultyId)
            ->where('year_of_study', $yearOfStudy)
            ->count();

        $onlineUsers = Student::where('faculty_id', $facultyId)
            ->where('year_of_study', $yearOfStudy)
            ->where('is_online', true)
            ->count();

        $viewedUsers = Student::where('faculty_id', $facultyId)
            ->where('year_of_study', $yearOfStudy)
            ->where('last_chat_access_at', '>=', now()->subDay())
            ->count();

        $remainingUsers = $totalUsers - $viewedUsers;

        return response()->json([
            'total_users' => $totalUsers,
            'online_users' => $onlineUsers,
            'viewed_users' => $viewedUsers,
            'remaining_users' => $remainingUsers,
        ], 200);
    }

    public function updateLastChatAccess()
    {
        $user = Auth::user();
        $user->last_chat_access_at = now();
        $user->save();
        return response()->json(['message' => 'Updated'], 200);
    }

      
}