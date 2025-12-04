<?php

namespace App\Jobs;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class SendNewsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $news;
    public $imageUrl;

    public function __construct($news, $imageUrl = null)
    {
        $this->news = $news;
        $this->imageUrl = $imageUrl;
    }

    public function handle()
    {
        $title = $this->news->title;
        $body  = Str::limit(strip_tags($this->news->description), 100);

        $messaging = Firebase::messaging();

        Student::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->select('fcm_token')
            ->chunk(500, function ($students) use ($messaging, $title, $body) {
                $tokens = $students->pluck('fcm_token')->filter()->values()->all();

                if (empty($tokens)) return;

                $notification = FirebaseNotification::create($title, $body);
                if ($this->imageUrl) {
                    $notification = $notification->withImageUrl($this->imageUrl);
                }

                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withData([
                        'type' => 'news',
                        'news_id' => (string) $this->news->id,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ])
                    ->withDefaultSounds();

                try {
                    $report = $messaging->sendMulticast($message, $tokens);

                    Log::info("News push sent", [
                        'news_id' => $this->news->id,
                        'success' => $report->successes()->count(),
                        'failed'  => $report->failures()->count(),
                    ]);

                    // Auto-remove invalid tokens
                    foreach ($report->invalidTokens() as $token) {
                        Student::where('fcm_token', $token)->update(['fcm_token' => null]);
                    }

                } catch (\Exception $e) {
                    Log::error("News FCM Error: " . $e->getMessage());
                }
            });
    }
}