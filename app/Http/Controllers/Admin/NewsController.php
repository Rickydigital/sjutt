<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Student;
use App\Jobs\SendNewsNotificationBatch;
use App\Jobs\SendNewsNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $search        = $request->query('search');
        $filterCreator = $request->query('filter_creator');

        $news = News::with('user')
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%")
                                      ->orWhere('description', 'like', "%{$search}%"))
            ->when($filterCreator, fn($q, $id) => $q->where('created_by', $id))
            ->latest()
            ->paginate(10);

        return view('admin.news.index', compact('news'));
    }

    public function create()
    {
        return view('admin.news.create');
    }

   public function store(Request $request)
{
    $request->validate([
        'title'       => 'required|string|max:255',
        'description' => 'required|string',
        'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'video'       => 'nullable|mimes:mp4,avi,mov|max:51200',
    ]);

    $news = new News($request->only(['title', 'description']));

    if ($request->hasFile('image')) {
        $news->image = $request->file('image')->store('news_images', 'public');
    }
    if ($request->hasFile('video')) {
        $news->video = $request->file('video')->store('news_videos', 'public');
    }

    $news->created_by = Auth::id();
    $news->save();

    // FIRE AND FORGET — INSTANT RESPONSE!
    SendNewsNotificationJob::dispatchAfterResponse(
        $news,
        $news->image ? Storage::url($news->image) : null
    );

    return redirect()->route('news.index')
        ->with('success', 'News posted successfully! Notification is being sent...');
}
    public function edit(News $news)
    {
        return view('admin.news.edit', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $request->validate([
            'title'       => 'required',
            'description' => 'required',
        ]);

        if ($request->hasFile('image')) {
            if ($news->image) {
                Storage::disk('public')->delete($news->image);
            }
            $news->image = $request->file('image')->store('news_images', 'public');
        }

        if ($request->hasFile('video')) {
            if ($news->video) {
                Storage::disk('public')->delete($news->video);
            }
            $news->video = $request->file('video')->store('news_videos', 'public');
        }

        $news->update($request->only(['title', 'description']));

        return redirect()->route('news.index')
            ->with('success', 'News updated successfully');
    }

    public function destroy(News $news)
    {
        if ($news->image) {
            Storage::disk('public')->delete($news->image);
        }
        if ($news->video) {
            Storage::disk('public')->delete($news->video);
        }
        $news->delete();

        return redirect()->route('news.index')
            ->with('success', 'News deleted');
    }

    // ──────────────────────────────────────────────────────────────
    // Send push to every student (chunked + queued)
    // ──────────────────────────────────────────────────────────────
    
   private function sendNewsToAllStudents(News $news): void
{
    $title = $news->title;
    $body  = Str::limit(strip_tags($news->description), 100);
    $image = $news->image ? Storage::url($news->image) : null;

    $chunkSize = 500;

    Student::query()
        ->whereNotNull('fcm_token')
        ->select('fcm_token')
        ->chunk($chunkSize, function ($students) use ($title, $body, $image) {
            $tokens = $students->pluck('fcm_token')->all();

            if (empty($tokens)) {
                Log::info("No FCM tokens found for news '{$title}'");
                return;
            }

            $this->sendFcmNotification($tokens, $title, $body, $image);
        });
}

private function sendFcmNotification(array $tokens, string $title, string $body, ?string $image = null): void
{
    $credentials = config('firebase.credentials');
    if (!$credentials || !file_exists($credentials)) {
        Log::error("Firebase credentials missing or invalid: " . ($credentials ?? 'null'));
        return;
    }

    try {
        $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($credentials);
        $messaging = $factory->createMessaging();

        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification([
                'title' => $title,
                'body'  => $body,
                'image' => $image,
            ])
            ->withData([
                'type' => 'news',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]);

        $report = $messaging->sendMulticast($message, $tokens);

        Log::info("News FCM sent: {$report->successes()->count()} success, {$report->failures()->count()} failed", [
            'title' => $title,
            'token_count' => count($tokens),
        ]);

        // Remove invalid tokens
        $invalidTokens = [];
        foreach ($report->failures() as $failure) {
            $token = $failure->target()->value();
            if ($token) {
                $invalidTokens[] = $token;
            }
        }

        if (!empty($invalidTokens)) {
            Student::whereIn('fcm_token', $invalidTokens)->update(['fcm_token' => null]);
            Log::info("Cleaned " . count($invalidTokens) . " invalid FCM tokens.");
        }

    } catch (\Throwable $e) {
        Log::error("News FCM failed: " . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
    }
}


}