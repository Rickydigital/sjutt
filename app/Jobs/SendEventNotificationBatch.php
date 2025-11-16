<?php
// app/Jobs/SendEventNotificationBatch.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Log;
use App\Models\Student;

class SendEventNotificationBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected array $tokens;
    protected string $title;
    protected string $body;
    protected ?string $image;

    public function __construct(array $tokens, string $title, string $body, ?string $image = null)
    {
        $this->tokens = array_values(array_filter($tokens));
        $this->title  = $title;
        $this->body   = $body;
        $this->image  = $image;
    }

    public function handle(): void
    {
        if (empty($this->tokens)) {
            Log::info("SendEventNotificationBatch: No tokens.");
            return;
        }

        $credentials = config('firebase.credentials');
        if (!$credentials || !file_exists($credentials)) {
            Log::error("Firebase credentials missing: " . ($credentials ?? 'null'));
            return;
        }

        try {
            $factory = (new Factory)->withServiceAccount($credentials);
            $messaging = $factory->createMessaging();

            $message = CloudMessage::new()
                ->withNotification([
                    'title' => $this->title,
                    'body'  => $this->body,
                    'image' => $this->image,
                ])
                ->withData([
                    'type' => 'event',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            $report = $messaging->sendMulticast($message, $this->tokens);

            $success = $report->successes()->count();
            $failed  = $report->failures()->count();

            Log::info("Event FCM Batch: {$success} sent, {$failed} failed", [
                'title' => $this->title,
            ]);

            $invalid = [];
            foreach ($report->failures() as $failure) {
                $token = $failure->target()->value();
                if ($token) $invalid[] = $token;
            }

            if (!empty($invalid)) {
                Student::whereIn('fcm_token', $invalid)->update(['fcm_token' => null]);
                Log::info("Cleaned " . count($invalid) . " invalid tokens (event).");
            }

        } catch (\Throwable $e) {
            Log::error("Event FCM Batch failed: " . $e->getMessage());
            throw $e;
        }
    }
}