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

class SendEventNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event;
    public $access;
    public $imageUrl;

    public function __construct($event, array $access, $imageUrl = null)
    {
        $this->event = $event;
        $this->access = $access;
        $this->imageUrl = $imageUrl;
    }

    public function handle()
    {
        $title = $this->event->title;
        $body  = Str::limit(strip_tags($this->event->description), 100);

        $messaging = Firebase::messaging();

        $query = Student::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->where('status', 'active');

        // Apply access rules
        if (!in_array('all', $this->access)) {
            $allowedRoles = [];
            if (in_array('student', $this->access)) $allowedRoles[] = 'student';
            if (in_array('staff', $this->access))   $allowedRoles[] = 'staff';

            $query->whereHas('roles', fn($q) => $q->whereIn('name', $allowedRoles));
        }

        $query->select('fcm_token')
              ->chunk(500, function ($students) use ($messaging, $title, $body) {
                  $tokens = $students->pluck('fcm_token')
                      ->filter()
                      ->unique()
                      ->values()
                      ->all();

                  if (empty($tokens)) return;

                  $notification = FirebaseNotification::create($title, $body);
                  if ($this->imageUrl) {
                      $notification = $notification->withImageUrl($this->imageUrl);
                  }

                  $message = CloudMessage::new()
                      ->withNotification($notification)
                      ->withData([
                          'type' => 'event',
                          'event_id' => (string) $this->event->id,
                          'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                      ])
                      ->withDefaultSounds();

                  try {
                      $report = $messaging->sendMulticast($message, $tokens);

                      Log::info("Event notification sent", [
                          'event_id' => $this->event->id,
                          'title' => $title,
                          'success' => $report->successes()->count(),
                          'failed' => $report->failures()->count(),
                      ]);

                      // Clean invalid tokens
                      foreach ($report->invalidTokens() as $token) {
                          Student::where('fcm_token', $token)->update(['fcm_token' => null]);
                      }

                  } catch (\Exception $e) {
                      Log::error("Event FCM failed: " . $e->getMessage());
                  }
              });
    }
}