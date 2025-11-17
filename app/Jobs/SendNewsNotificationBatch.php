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

class SendNewsNotificationBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /** @var array<int, string> */
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
            Log::info("SendNewsNotificationBatch: No tokens to send.");
            return;
        }

        // === SAME AS TIMETABLE COMMAND ===
        $credentials = config('firebase.credentials');
        if (!$credentials || !file_exists($credentials)) {
            Log::error("Firebase credentials missing or invalid: " . ($credentials ?? 'null'));
            return;
        }

        try {
            $factory = (new Factory)->withServiceAccount($credentials);
            $messaging = $factory->createMessaging();

            // Build message (same structure)
            $message = CloudMessage::new()
                ->withNotification([
                    'title' => $this->title,
                    'body'  => $this->body,
                    'image' => $this->image,
                ])
                ->withData([
                    'type' => 'news',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            // === MULTICAST SEND (500 tokens max per request) ===
            $report = $messaging->sendMulticast($message, $this->tokens);

            $success = $report->successes()->count();
            $failed  = $report->failures()->count();

            Log::info("News FCM Batch: {$success} sent, {$failed} failed", [
                'title' => $this->title,
                'token_count' => count($this->tokens),
            ]);

            // === CLEAN INVALID TOKENS ===
            $invalidTokens = [];
            foreach ($report->failures() as $failure) {
                $token = $failure->target()->value();
                if ($token) {
                    $invalidTokens[] = $token;
                }
            }

            if (!empty($invalidTokens)) {
                Student::whereIn('fcm_token', $invalidTokens)
                    ->update(['fcm_token' => null]);

                Log::info("Cleaned " . count($invalidTokens) . " invalid FCM tokens.");
            }

        } catch (\Throwable $e) {
            Log::error("News FCM Batch failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Let Laravel retry
        }
    }
}