<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Notifications\NewMessageNotification;

class SuggestionController extends Controller {
    public function store( Request $request ) {
        try {
            Log::info( 'Starting suggestion store', [ 'input' => $request->all() ] );

            $request->validate( [
                'message' => 'required|string|max:1000',
            ] );

            $suggestion = Suggestion::create( [
                'student_id' => null,
                'user_id' => null,
                'sender_type' => 'student',
                'message' => $request->message,
                'is_anonymous' => true,
                'status' => 'Received', 
            ] ); 

            $admins = \App\Models\User::role( 'admin' )->get();

            foreach ( $admins as $admin ) {
                try {
                    $admin->notify( new NewMessageNotification( $suggestion ) );
                    Log::info( "Notification sent to admin {$admin->id} ({$admin->email})" );
                } catch ( \Exception $e ) {
                    Log::error( "Failed to notify admin {$admin->id}: {$e->getMessage()}" );
                }
            }

            // Log::info( "Notified {$admins->count()} admins for suggestion #{$suggestion->id}" );

            return response()->json( [ 'status' => 'success', 'message' => 'Suggestion sent successfully' ] );
        } catch ( \Exception $e ) {
            Log::error( 'Suggestion store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ] );
            return response()->json( [ 'status' => 'error', 'message' => 'There was an error, please try again later' ], 500 );
        }
    }

    public function index( Request $request ) {
        try {
            $student = $request->user();
            if ( !$student ) {
                Log::error( 'No authenticated user found' );
                return response()->json( [ 'success' => false, 'message' => 'Unauthorized' ], 401 );
            }

            $suggestions = Suggestion::where( function ( $query ) use ( $student ) {
                $query->where( 'student_id', $student->id ) // Messages related to the student
                ->where( function ( $subQuery ) {
                    $subQuery->where( 'sender_type', 'student' ) // Student's own messages
                                   ->orWhere('sender_type', 'admin'); // Admin replies to the student
                      });
            })
            ->orWhere(function ($query) use ($student) {
                $query->where('is_anonymous', true) // Anonymous messages from others
                      ->where('student_id', ' != ', $student->id)
                      ->where('sender_type', 'student');
            })
            ->where(function ($query) use ($student) {
                $query->whereNull('deleted_for')
                      ->orWhereNot('deleted_for', 'like', '%"' . $student->id . '"%');
            })
            ->with(['user' => function ($query) {
                $query->select('id', 'name', 'email');
            }])
            ->orderBy('created_at', 'asc')
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

    public function delete(Request $request, $id)
    {
        try {
            $student = $request->user();
            if (!$student) {
                Log::error('No authenticated user found');
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $request->validate([
                'delete_type' => 'required|in:for_me, for_all',
            ]);

            $suggestion = Suggestion::find($id);
            if (!$suggestion) {
                Log::warning("Suggestion #$id not found");
                return response()->json(['success' => false, 'message' => 'Message not found'], 404);
            }

            // Check if the user is the sender
            if ($suggestion->student_id != $student->id) {
                Log::warning("Student {$student->id} attempted to delete suggestion #$id they did not send");
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($request->delete_type == 'for_all') {
                // Delete the message from the database entirely
                $suggestion->delete();
                Log::info("Suggestion #$id deleted for all by student {$student->id}");
                return response()->json(['success' => true, 'message' => 'Message deleted for all']);
            } else {
                // Delete for me: Add student_id to deleted_for array
                $deletedFor = $suggestion->deleted_for ? json_decode($suggestion->deleted_for, true) : [];
                if (!in_array($student->id, $deletedFor)) {
                    $deletedFor[] = $student->id;
                    $suggestion->deleted_for = json_encode($deletedFor);
                    $suggestion->save();
                    Log::info("Suggestion #$id deleted for student {$student->id}");
                }
                return response()->json(['success' => true, 'message' => 'Message deleted for you']);
            }
        } catch (\Exception $e) {
            Log::error("Error deleting suggestion #$id: {$e->getMessage()}");
            return response()->json(['success' => false, 'message' => 'Server error' ], 500 );
                }
            }
        }