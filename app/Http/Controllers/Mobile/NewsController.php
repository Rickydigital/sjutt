<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\News;
use App\Models\Reaction;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class NewsController extends Controller
{
    
    public function index()
    {
        try {
            $news = News::with(['user', 'reactions.reactable', 'comments.commentable'])->latest()->get();
            return response()->json([
                'success' => true,
                'message' => 'News fetched successfully',
                'data' => $news
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch news',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $news = News::with(['user', 'reactions.reactable', 'comments.commentable'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'News retrieved successfully',
                'data' => $news
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'News not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
      
    public function comment(Request $request, $id) {
        $request->validate(['comment' => 'required|string|max:500']);
        $news = News::findOrFail($id);
        $user = Auth::guard('sanctum')->user();
      
        if (!$user) {
          return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
      
        $comment = Comment::create([
          'commentable_id' => $user->id,
          'commentable_type' => get_class($user),
          'news_id' => $news->id,
          'comment' => $request->comment,
        ]);
      
        return response()->json([
          'success' => true,
          'message' => 'Comment added successfully',
          'data' => $comment->load('commentable')
        ], 201);
      }

    public function react(Request $request, $id)
    {
        try {
            $request->validate([
                'type' => 'required|in:like,dislike',
            ]);

            $news = News::findOrFail($id);
            $user = Auth::user(); // Could be User or Student

            $existingReaction = Reaction::where('news_id', $news->id)
                                        ->where('reactable_id', $user->id)
                                        ->where('reactable_type', get_class($user))
                                        ->first();

            if ($existingReaction) {
                if ($existingReaction->type === $request->type) {
                    $existingReaction->delete();
                    return response()->json([
                        'success' => true,
                        'message' => 'Reaction removed successfully',
                        'data' => null
                    ], 200);
                } else {
                    $existingReaction->update(['type' => $request->type]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Reaction updated successfully',
                        'data' => $existingReaction
                    ], 200);
                }
            } else {
                $reaction = Reaction::create([
                    'reactable_id' => $user->id,
                    'reactable_type' => get_class($user),
                    'news_id' => $news->id,
                    'type' => $request->type,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reaction added successfully',
                    'data' => $reaction
                ], 201);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'News not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add reaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFaculties()
    {
        try {
            $faculties = Faculty::all();
            return response()->json([
                'success' => true,
                'message' => 'Faculties fetched successfully',
                'data' => $faculties
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch faculties',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'reg_no' => 'required|string|unique:students,reg_no',
            'year_of_study' => 'required|integer|between:1,4',
            'faculty_id' => 'required|exists:faculties,id',
            'email' => 'required|email|unique:students,email',
            'password' => 'required|string|min:6',
            'gender' => 'required|in:male,female,other',
        ]);

        $student = Student::create([
            'name' => $request->name,
            'reg_no' => $request->reg_no,
            'year_of_study' => $request->year_of_study,
            'faculty_id' => $request->faculty_id,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'gender' => $request->gender,
        ]);

        $token = $student->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => ['token' => $token, 'student' => $student->load('faculty')]
        ], 201);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);
    
            // Find the student by email
            $student = Student::where('email', $request->email)->first();
    
            // Check if student exists and password is correct
            if (!$student || !Hash::check($request->password, $student->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }
    
            // Generate a Sanctum token
            $token = $student->createToken('mobile-app')->plainTextToken;
    
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'student' => $student
                ]
            ], 200);
    
        } catch (\Exception $e) {
            \Log::error('Login Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


        public function storeToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $student = Auth::guard('sanctum')->user();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $student->fcm_token = $request->token;
        $student->save();

        return response()->json(['success' => true, 'message' => 'FCM token stored successfully'], 200);
    }

    public function updateOnlineStatus(Request $request)
    {
        $user = $request->user();
        $student = Student::find($user->id);

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found'], 404);
        }

        $student->is_online = $request->input('is_online', false);
        $student->save();

        Log::info("Student {$student->id} online status updated to {$student->is_online}");

        return response()->json(['success' => true]);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out successfully'], 200);
    }

    public function profile(Request $request) {
        return response()->json(['success' => true, 'data' => $request->user()], 200);
    }

    public function editProfile(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'reg_no' => 'required|string|unique:students,reg_no,' . $request->user()->id,
            'year_of_study' => 'required|integer|between:1,4',
            'email' => 'required|email|unique:students,email,' . $request->user()->id,
            'gender' => 'required|in:male,female,other',
        ]);

        $student = $request->user();
        $student->update($request->only('name', 'reg_no', 'year_of_study', 'email', 'gender'));
        return response()->json(['success' => true, 'message' => 'Profile updated', 'data' => $student], 200);
    }

    public function changePassword(Request $request) {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $student = $request->user();
        if (!Hash::check($request->current_password, $student->password)) {
            return response()->json(['success' => false, 'message' => 'Current password incorrect'], 401);
        }

        $student->password = bcrypt($request->new_password);
        $student->save();
        return response()->json(['success' => true, 'message' => 'Password changed'], 200);
    }

    public function forgotPassword(Request $request) {
        $request->validate(['email' => 'required|email']);
        $student = Student::where('email', $request->email)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Email not found'], 404);
        }

        // Simulate sending reset email (implement actual email logic with Laravel Mail)
        return response()->json(['success' => true, 'message' => 'Reset email sent'], 200);
    }
    
}