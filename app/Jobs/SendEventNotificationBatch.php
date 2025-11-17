<?php

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
    public $backoff = [10, 30, 60];

    protected array $tokens;
    protected string $title;
    protected string $body;
    protected ?string $image;

    public function __construct(array $tokens, string $title, string $body, ?string $image = null)
    {
        $this->tokens = array_values(array_unique(array_filter($tokens)));
        $this->title  = $title;
        $this->body   = $body;
        $this->image  = $image;
    }

    public function handle(): void
    {
        if (empty($this->tokens)) {
            Log::info("SendEventNotificationBatch: No tokens to send.");
            return;
        }

        $credentials = config('firebase.credentials');
        if (!$credentials || !file_exists($credentials)) {
            Log::error("Firebase credentials missing or invalid: " . ($credentials ?? 'null'));
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

            Log::info("Event FCM Batch: {$success} success, {$failed} failed", [
                'title' => $this->title,
                'token_count' => count($this->tokens),
            ]);

            $invalidTokens = [];
            foreach ($report->failures() as $index => $failure) {
                $token = $failure->target()->value();
                $error = $failure->error();

                Log::warning("FCM Failure #{$index}", [
                    'token' => $token,
                    'reason' => $error?->getReason() ?? 'unknown',
                    'message' => $error?->getMessage() ?? 'No message',
                    'code' => $error?->getCode() ?? 'N/A',
                ]);

                if ($token && in_array($error?->getReason(), ['UNREGISTERED', 'INVALID_REGISTRATION', 'NOT_FOUND'])) {
                    $invalidTokens[] = $token;
                }
            }

            if (!empty($invalidTokens)) {
                $cleaned = Student::whereIn('fcm_token', $invalidTokens)->update(['fcm_token' => null]);
                Log::info("Cleaned {$cleaned} invalid FCM tokens for event: {$this->title}");
            }

        } catch (\Throwable $e) {
            Log::error("Event FCM Batch failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'title' => $this->title,
            ]);
            throw $e; // Let Laravel retry
        }
    }
}