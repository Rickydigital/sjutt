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
use Illuminate\Support\Facades\Validator;


class NewsController extends Controller
{
    
   

    public function latest()
    {
        try {
            $news = News::with(['user', 'reactions', 'comments.commentable'])
                ->latest()
                ->take(5)
                ->get();
            $news = $news->map(function ($item) {
                $item->reactions = $item->reactions->map(function ($reaction) {
                    $mapped = [
                        'id' => $reaction->id,
                        'type' => $reaction->type,
                        'user_id' => (int) $reaction->reactable_id,
                        'news_id' => $reaction->news_id
                    ];
                    Log::info("Mapped reaction for news {$reaction->news_id}: " . json_encode($mapped));
                    return $mapped;
                });
                // Convert relative image path to full URL
                if ($item->image) {
                    $item->image = asset('storage/' . ltrim($item->image, '/')); // e.g., http://192.168.137.15:8000/storage/news_images/...
                }
                return $item;
            });
    
            return response()->json([
                'success' => true,
                'message' => 'Latest news fetched successfully',
                'data' => $news
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to fetch latest news: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch latest news',
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
              $user = Auth::guard('sanctum')->user(); // Use sanctum guard for API
      
              if (!$user) {
                  return response()->json([
                      'success' => false,
                      'message' => 'Unauthorized'
                  ], 401);
              }
      
              $existingReaction = Reaction::where('news_id', $news->id)
                                          ->where('reactable_id', $user->id)
                                          ->where('reactable_type', get_class($user))
                                          ->where('type', $request->type)
                                          ->first();
      
              if ($existingReaction) {
                  // Same reaction type exists, no need to keep it (client expects toggle)
                  $existingReaction->delete();
                  Log::info("Reaction {$request->type} removed for news {$news->id} by user {$user->id}");
                  return response()->json([
                      'success' => true,
                      'message' => 'Reaction removed successfully',
                      'data' => null
                  ], 200);
              }
      
              // Remove opposite reaction if it exists (e.g., dislike if liking)
              Reaction::where('news_id', $news->id)
                      ->where('reactable_id', $user->id)
                      ->where('reactable_type', get_class($user))
                      ->where('type', $request->type === 'like' ? 'dislike' : 'like')
                      ->delete();
      
              // Create new reaction
              $reaction = Reaction::create([
                  'reactable_id' => $user->id,
                  'reactable_type' => get_class($user),
                  'news_id' => $news->id,
                  'type' => $request->type,
              ]);
      
              Log::info("Reaction {$request->type} added for news {$news->id} by user {$user->id}");
      
              return response()->json([
                  'success' => true,
                  'message' => 'Reaction added successfully',
                  'data' => [
                      'id' => $reaction->id,
                      'type' => $reaction->type,
                      'user_id' => $reaction->reactable_id, // Map to user_id for client
                      'news_id' => $reaction->news_id
                  ]
              ], 201);
          } catch (ModelNotFoundException $e) {
              Log::error("News {$id} not found");
              return response()->json([
                  'success' => false,
                  'message' => 'News not found'
              ], 404);
          } catch (\Exception $e) {
              Log::error("Failed to add reaction: {$e->getMessage()}");
              return response()->json([
                  'success' => false,
                  'message' => 'Failed to add reaction',
                  'error' => $e->getMessage()
              ], 500);
          }
      }

      public function removeReaction(Request $request, $id)
      {
          try {
              $request->validate([
                  'type' => 'required|in:like,dislike',
              ]);
      
              $news = News::findOrFail($id);
              $user = Auth::guard('sanctum')->user();
      
              if (!$user) {
                  return response()->json([
                      'success' => false,
                      'message' => 'Unauthorized'
                  ], 401);
              }
      
              $deleted = Reaction::where('news_id', $news->id)
                                 ->where('reactable_id', $user->id)
                                 ->where('reactable_type', get_class($user))
                                 ->where('type', $request->type)
                                 ->delete();
      
              if ($deleted) {
                  Log::info("Reaction {$request->type} removed for news {$news->id} by user {$user->id}");
                  return response()->json([
                      'success' => true,
                      'message' => 'Reaction removed successfully',
                      'data' => null
                  ], 200);
              }
      
              Log::warning("No {$request->type} reaction found for news {$news->id} by user {$user->id}");
              return response()->json([
                  'success' => true,
                  'message' => 'No reaction to remove',
                  'data' => null
              ], 200);
          } catch (ModelNotFoundException $e) {
              Log::error("News {$id} not found");
              return response()->json([
                  'success' => false,
                  'message' => 'News not found'
              ], 404);
          } catch (\Exception $e) {
              Log::error("Failed to remove reaction: {$e->getMessage()}");
              return response()->json([
                  'success' => false,
                  'message' => 'Failed to remove reaction',
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
        $validator = Validator::make($request->all(), [
            'reg_no' => 'required|string',
            'password' => 'required',
            'fcm_token' => 'nullable|string', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        
        $student = Student::where('reg_no', $request->reg_no)->first();

        // Check if student exists and password is correct
        if (!$student || !Hash::check($request->password, $student->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

       
        $newToken = $request->input('fcm_token');
        $oldToken = $student->fcm_token;

        if ($newToken && $newToken !== $oldToken) {
            $student->fcm_token = $newToken;
            $student->save();
            Log::info("FCM token updated for student {$student->id}: {$oldToken} → {$newToken}");
        } elseif (!$newToken && $oldToken) {
            Log::warning("App sent no FCM token, but DB has one for student {$student->id}");
        }
       

        // Generate Sanctum token
        $token = $student->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'student' => $student->makeHidden(['fcm_token']) // Hide sensitive field
            ]
        ], 200);

    } catch (\Exception $e) {
        Log::error('Login Error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Login failed',
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

        $student = Student::find($request->user()->id);
        if (!$student || !Hash::check($request->current_password, $student->password)) {
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