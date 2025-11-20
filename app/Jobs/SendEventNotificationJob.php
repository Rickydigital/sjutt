<?php

namespace App\Jobs;

use App\Models\Event;
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

class SendEventNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;
    protected $access;
    protected $imageUrl;

    public function __construct(Event $event, array $access, ?string $imageUrl)
    {
        $this->event = $event;
        $this->access = $access;
        $this->imageUrl = $imageUrl;
    }


    
    public function handle()
    {
        // Make sure script runs even after client disconnects
        ignore_user_abort(true);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $title = $this->event->title;
        $body  = Str::limit(strip_tags($this->event->description), 100);

        $query = Student::whereNotNull('fcm_token');

        if (!in_array('all', $this->access)) {
            $allowedRoles = [];
            if (in_array('student', $this->access)) $allowedRoles[] = 'student';
            if (in_array('staff', $this->access))   $allowedRoles[] = 'staff';
            $query->whereHas('roles', fn($q) => $q->whereIn('name', $allowedRoles));
        }

        $query->select('id', 'fcm_token')
              ->chunk(500, function ($students) use ($title, $body) {
                  $tokens = $students->pluck('fcm_token')
                      ->filter(fn($t) => is_string($t) && strlen($t) > 50)
                      ->unique()
                      ->values()
                      ->all();

                  if (empty($tokens)) return;

                  $this->sendFcmBatch($tokens, $title, $body);
              });
    }

    private function sendFcmBatch(array $tokens, string $title, string $body)
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
                    'type' => 'event',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            $messaging->sendMulticast($message, $tokens);
        } catch (\Throwable $e) {
            Log::error("FCM failed in background: " . $e->getMessage());
        }
    }
}