<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\TalentContent;
use App\Models\TalentLike;
use App\Models\TalentComment;
use App\Models\FlaggedContent;
use App\Models\Student;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

class TalentController extends Controller
{
    public function index()
    {
        try {
            $talents = TalentContent::with(['student', 'likes', 'comments.student'])
                ->where('status', 'approved')
                ->latest()
                ->get();
    
            $talents->transform(function ($item) {
                // The file_path is already relative, e.g., "talents/video.mp4".
                // Ensure the video_url for streaming is also relative.
                $item->video_url = 'talents/' . basename($item->file_path);

                // No need to strip 'public/' since file_path is 'talents/<filename>'
                $item->likes = $item->likes->map(function ($like) {
                    return [
                        'id' => $like->id,
                        'student_id' => $like->student_id,
                        'talent_content_id' => $like->talent_content_id,
                    ];
                });
                return $item;
            });
    
            return response()->json([
                'success' => true,
                'message' => 'Talent content fetched successfully',
                'data' => $talents
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to fetch talent content: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch talent content',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function myTalents()
    {
        try {
            $student = Auth::guard('sanctum')->user();
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
    
            $talents = TalentContent::with(['student', 'likes', 'comments.student', 'flaggedContent'])
                ->where('student_id', $student->id)
                ->latest()
                ->get();
    
            $talents->transform(function ($item) {
                // The file_path is already relative, e.g., "talents/video.mp4".
                // Ensure the video_url for streaming is also relative.
                $item->video_url = 'talents/' . basename($item->file_path);

                return $item;
            });
    
            return response()->json([
                'success' => true,
                'message' => 'Your talents fetched successfully',
                'data' => $talents
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to fetch user's talents: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch your talents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'content_type' => 'required|in:video,audio,image',
                'file' => 'required|file|mimes:mp4,mp3,jpeg,png,gif|max:10240',
                'description' => 'required|string|max:1000',
                'social_media_link' => 'nullable|url',
            ]);
    
            $student = Auth::guard('sanctum')->user();
            Log::info('Authenticated user', ['student' => $student]);
    
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
    
            if (!$student->can_upload) {
                return response()->json(['success' => false, 'message' => 'You are banned from uploading content'], 403);
            }
    
            $file = $request->file('file');
            // Explicitly store in public/talents
            $filePath = $file->store('talents', 'public'); // Stores in storage/app/public/talents/
            $fileUrl = Storage::url($filePath); // Generates /storage/talents/<filename>
    
            $talent = TalentContent::create([
                'student_id' => $student->id,
                'content_type' => $request->content_type,
                'file_path' => $filePath, // Store as 'talents/<filename>'
                'description' => $request->description,
                'social_media_link' => $request->social_media_link,
                'status' => 'pending',
            ]);
    
            // Temporarily approve (as per your code)
            $talent->status = 'approved';
            $talent->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Content uploaded successfully',
                'data' => $talent
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error storing talent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload talent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function moderateContent(TalentContent $talent)
    {
        try {
            $imageAnnotator = new \Google\Cloud\Vision\V1\Client\ImageAnnotatorClient();
            $filePath = storage_path('app/' . $talent->file_path);
            $image = file_get_contents($filePath);

            $response = $imageAnnotator->safeSearchDetection($image);
            $safeSearch = $response->getSafeSearchAnnotation();

            $isInappropriate = $safeSearch->getAdult() >= 3 || $safeSearch->getViolence() >= 3;
            if ($isInappropriate) {
                $talent->status = 'flagged';
                $talent->save();

                FlaggedContent::create([
                    'talent_content_id' => $talent->id,
                    'reason' => 'Inappropriate content detected (adult or violence)',
                    'flagged_by' => 'system',
                ]);
            } else {
                $talent->status = 'approved';
                $talent->save();
            }

            $imageAnnotator->close();
        } catch (\Exception $e) {
            Log::error("Content moderation failed: {$e->getMessage()}");
            $talent->status = 'flagged';
            $talent->save();

            FlaggedContent::create([
                'talent_content_id' => $talent->id,
                'reason' => 'Moderation failed: ' . $e->getMessage(),
                'flagged_by' => 'system',
            ]);
        }
    }

    public function like(Request $request, $id)
    {
        try {
            $talent = TalentContent::findOrFail($id);
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $existingLike = TalentLike::where('talent_content_id', $talent->id)
                ->where('student_id', $user->id)
                ->first();

            if ($existingLike) {
                $existingLike->delete();
                Log::info("Like removed for talent {$talent->id} by user {$user->id}");
                return response()->json([
                    'success' => true,
                    'message' => 'Like removed successfully',
                    'data' => null
                ], 200);
            }

            $like = TalentLike::create([
                'talent_content_id' => $talent->id,
                'student_id' => $user->id,
            ]);

            Log::info("Like added for talent {$talent->id} by user {$user->id}");

            return response()->json([
                'success' => true,
                'message' => 'Like added successfully',
                'data' => [
                    'id' => $like->id,
                    'student_id' => $like->student_id,
                    'talent_content_id' => $like->talent_content_id,
                ]
            ], 201);
        } catch (ModelNotFoundException $e) {
            Log::error("Talent {$id} not found");
            return response()->json([
                'success' => false,
                'message' => 'Talent content not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Failed to add/remove like: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to add/remove like',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function comment(Request $request, $id)
    {
        $request->validate(['comment' => 'required|string|max:500']);
        $talent = TalentContent::findOrFail($id);
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $comment = TalentComment::create([
            'talent_content_id' => $talent->id,
            'student_id' => $user->id,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment->load('student')
        ], 201);
    }

   
    public function delete($id)
    {
        try {
            $student = Auth::guard('sanctum')->user();
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $talent = TalentContent::where('student_id', $student->id)
                ->where('id', $id)
                ->firstOrFail();

            Storage::delete($talent->file_path);
            $talent->delete();

            return response()->json([
                'success' => true,
                'message' => 'Talent deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error("Talent {$id} not found or not owned by user");
            return response()->json([
                'success' => false,
                'message' => 'Talent not found or you do not have permission to delete it'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Failed to delete talent {$id}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete talent',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}