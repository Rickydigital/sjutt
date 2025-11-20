<?php

namespace App\Jobs;

use App\Models\News;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;



class SendNewsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $news;
    protected $imageUrl;

    public function __construct(News $news, ?string $imageUrl = null)
    {
        $this->news = $news;
        $this->imageUrl = $imageUrl;
    }

    public function handle()
    {
        // Run in background even after response is sent
       ignore_user_abort(true);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $title = $this->news->title;
        $body  = Str::limit(strip_tags($this->news->description), 100);

        Student::whereNotNull('fcm_token')
            ->select('fcm_token')
            ->chunk(500, function ($students) use ($title, $body) {
                $tokens = $students->pluck('fcm_token')
                    ->filter(fn($t) => is_string($t) && strlen($t) > 50)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($tokens)) return;

                $this->sendBatch($tokens, $title, $body);
            });
    }

    private function sendBatch(array $tokens, string $title, string $body)
    {
        try {
            $messaging = (new Factory)
                ->withServiceAccount(config('firebase.credentials'))
                ->createMessaging();

            $message = CloudMessage::new()
                ->withNotification([
                    'title' => $title,
                    'body'  => $body,
                    'image' => $this->imageUrl,
                ])
                ->withData([
                    'type' => 'news',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            $report = $messaging->sendMulticast($message, $tokens);

            Log::info("News FCM batch sent", [
                'title' => $title,
                'success' => $report->successes()->count(),
                'failed' => $report->failures()->count(),
            ]);

            // Clean invalid tokens
            $invalid = [];
            foreach ($report->failures() as $failure) {
                $token = $failure->target()->value();
                if ($token) $invalid[] = $token;
            }

            if ($invalid) {
                Student::whereIn('fcm_token', $invalid)->update(['fcm_token' => null]);
            }

        } catch (\Throwable $e) {
            Log::error("News FCM job failed: " . $e->getMessage());
        }
    }
}