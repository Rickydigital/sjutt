<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Calendar;
use App\Models\Student;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Log;

class SendCalendarNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        Log::info('SendCalendarNotifications job started at ' . now()->toDateTimeString());

        $events = Calendar::getTodayEvents();
        Log::info('Found ' . $events->count() . ' calendar events', $events->toArray());

        if ($events->isEmpty()) {
            Log::info('No calendar events for today');
            return;
        }

        $credentials = config('firebase.credentials');
        Log::info('Firebase credentials path: ' . ($credentials ?? 'null'));

        if (!$credentials || !file_exists($credentials)) {
            Log::error('Firebase credentials invalid: ' . ($credentials ?? 'null'));
            return;
        }

        $factory = (new Factory)->withServiceAccount($credentials);
        $messaging = $factory->createMessaging();

        $students = Student::whereNotNull('fcm_token')
            ->where('is_online', true)
            ->get();
        Log::info('Found ' . $students->count() . ' online students with FCM tokens', $students->pluck('id', 'name')->toArray());

        foreach ($students as $student) {
            $eventList = $events->map(function ($event, $index) {
                return "- {$event['description']}";
            })->implode("\n");

            $messageBody = "Hi {$student->name},\nToday there is:\n{$eventList}";
            Log::info('Preparing notification for student ' . $student->id . ': ' . $messageBody);

            $message = CloudMessage::withTarget('token', $student->fcm_token)
                ->withNotification([
                    'title' => 'Today’s Events',
                    'body' => $messageBody,
                ])
                ->withData([
                    'type' => 'calendar_event',
                    'events' => json_encode($events->toArray()),
                ]);

            try {
                $messaging->send($message);
                Log::info("Calendar notification sent to student {$student->id}: {$messageBody}");
            } catch (\Exception $e) {
                Log::error("Failed to send notification to student {$student->id}: {$e->getMessage()}");
            }
        }
    }
}