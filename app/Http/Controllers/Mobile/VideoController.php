<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    /**
     * Stream a video file from storage.
     *
     * @param string $folder The folder within storage/app/public where the video is located.
     * @param string $filename The name of the video file.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
     */
    public function stream($folder, $filename)
{
    // Debug logs (remove in prod)
    $relativePath = $folder . '/' . $filename;
    Log::info("Streaming: folder={$folder}, filename={$filename}, path={$relativePath}");
    
    // Use public disk for clarity
    if (!Storage::disk('public')->exists($relativePath)) {
        $fullPath = Storage::disk('public')->path($relativePath);
        Log::error("Video missing: {$fullPath}");
        abort(404, 'Video not found.');
    }
    
    $disk = Storage::disk('public');
    $file = $disk->path($relativePath);
    $size = $disk->size($relativePath);
    $mime = $disk->mimeType($relativePath) ?: 'video/mp4'; // Fallback
    
    $headers = [
        'Content-Type' => $mime,
        'Accept-Ranges' => 'bytes',
        'Content-Length' => $size,
        'Cache-Control' => 'public, max-age=3600', // Optional: Cache for 1hr
    ];
    
    $status = 200;
    $start = 0;
    $end = $size - 1;
    $length = $size;
    
    // Handle Range
    if (request()->hasHeader('Range')) {
        $range = request()->header('Range');
        if (preg_match('/bytes=(\d+)-(\d+)?/', $range, $matches)) {
            $start = intval($matches[1]);
            $end = isset($matches[2]) && $matches[2] !== '' ? intval($matches[2]) : $size - 1;
            
            if ($start > $end || $start >= $size) {
                return response('Requested Range Not Satisfiable', 416, $headers);
            }
            
            $length = $end - $start + 1;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
            $headers['Content-Length'] = $length;
            $status = 206;
        }
    }
    
    // Streamed response with chunking to avoid memory issues
    return response()->stream(function () use ($file, $start, $length) {
        try {
            $handle = fopen($file, 'rb');
            if (!$handle) {
                throw new \Exception('Failed to open file');
            }
            
            fseek($handle, $start);
            $bufferSize = 1024 * 1024; // 1MB chunks (adjust as needed)
            $remaining = $length;
            
            while ($remaining > 0 && !feof($handle)) {
                $chunkSize = min($bufferSize, $remaining);
                $data = fread($handle, $chunkSize);
                if ($data === false || strlen($data) === 0) {
                    break;
                }
                echo $data;
                $remaining -= strlen($data);
                flush(); // Send chunk immediately
            }
            
            fclose($handle);
        } catch (\Exception $e) {
            Log::error("Stream error: " . $e->getMessage());
            echo ''; // Empty response on error
        }
    }, $status, $headers);
}

    /*
    |--------------------------------------------------------------------------
    | How to Use This Video Streaming Controller
    |--------------------------------------------------------------------------
    |
    | This controller is designed to be a centralized, reusable solution for
    | streaming video files across your entire application.
    |
    | 1. How It Works:
    |    - It accepts a `folder` and `filename` as parameters.
    |    - It constructs a path to the file within your `storage/app/public` directory.
    |    - It correctly handles HTTP Range Requests, which is essential for video players
    |      to buffer and play content without downloading the entire file first.
    |    - It returns a `206 Partial Content` response for chunks and a `200 OK`
    |      response with the full file if streaming is not requested.
    |
    | 2. How to Use for Other Video Types (e.g., Talent Videos):
    |
    |    Step A: Define a Route
    |    The route is already generic enough. It's defined in `routes/api.php`:
    |    Route::get('/stream/video/{folder}/{filename}', [VideoController::class, 'stream'])->name('video.stream');
    |
    |    Step B: Generate the Streaming URL in Your Controllers
    |    Whenever you need to return a video URL in an API response, use the `route()`
    |    helper to point to this streaming endpoint.
    |
    |    Example for a 'Talent' video stored in `storage/app/public/talents/`:
    |
    |    // In your TalentController, instead of this:
    |    // 'video_url' => Storage::url($talent->file_path),
    |
    |    // Do this:
    |    'video_url' => route('video.stream', [
    |        'folder' => 'talents', // The sub-directory in `public`
    |        'filename' => basename($talent->file_path) // Just the filename
    |    ]),
    |
    | 3. Efficiency and Security:
    |    - This approach is highly efficient as it reads and sends only small chunks
    |      of the file from the disk, keeping memory usage low.
    |    - IMPORTANT: Before streaming, you should implement authorization logic
    |      (commented out in the code) to ensure that the authenticated user has
    |      permission to view the requested video file. This prevents unauthorized access.
    |
    */
}