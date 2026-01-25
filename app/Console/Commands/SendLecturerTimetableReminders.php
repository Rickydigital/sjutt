<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Timetable;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\Log;

class SendLecturerTimetableReminders extends Command
{
    protected $signature = 'lecturers:timetable-remind';
    protected $description = 'Send upcoming lecture reminders to lecturers 30 minutes before their classes';

    public function handle()
    {
        $now = Carbon::now('Africa/Dar_es_Salaam');
        $this->info("Running lecturer timetable reminders at {$now}");

        // Skip on Sundays (assuming no classes)
        if ($now->isSunday()) {
            $this->info('Today is Sunday – no lecture reminders sent.');
            Log::info('Lecturer reminders skipped: Sunday');
            return;
        }

        $credentials = config('firebase.credentials');
        if (!$credentials || !file_exists($credentials)) {
            $this->error("Firebase credentials file not found or invalid.");
            Log::error("Firebase credentials missing for lecturer reminders.");
            return;
        }

        $messaging = (new Factory)->withServiceAccount($credentials)->createMessaging();

        $this->sendUpcomingLectureReminders($messaging, $now);
    }

    private function sendUpcomingLectureReminders($messaging, Carbon $now)
    {
        $day = $now->format('l'); // e.g., Monday
        $currentTime = $now->format('H:i:s');
        $in30Minutes = $now->copy()->addMinutes(30)->format('H:i:s');

        // Find classes starting in the next 30 minutes
        $upcomingClasses = Timetable::query()
            ->where('day', $day)
            ->where('time_start', '>', $currentTime)
            ->where('time_start', '<=', $in30Minutes)
            ->whereNotNull('lecturer_id')
            ->with(['lecturer', 'venue', 'faculty', 'course'])
            ->get();

        if ($upcomingClasses->isEmpty()) {
            $this->info('No upcoming lectures in the next 30 minutes.');
            Log::info('No upcoming lectures found for lecturer reminders.');
            return;
        }

        $this->info("Found {$upcomingClasses->count()} upcoming lecture(s) for reminders.");

        foreach ($upcomingClasses as $tt) {
            $lecturer = $tt->lecturer;

            if (!$lecturer || !$lecturer->fcm_token) {
                $this->warn("Skipping reminder: No lecturer or FCM token for timetable ID {$tt->id}");
                continue;
            }

            $startTime = Carbon::parse($tt->time_start)->format('H:i');
            $venue = $tt->venue?->longform ?? $tt->venue?->name ?? 'TBD';
            $facultyName = $tt->faculty?->name ?? 'Unknown Class';
            $courseName = $tt->course?->name ?? $tt->course_code;
            $group = $tt->group_selection ? "{$tt->group_selection}" : 'All Groups';

            $title = "Teaching Reminder";
            $body = "You have a class in 30 minutes!\n" .
                    "{$tt->course_code} - {$courseName}\n" .
                    "Activity: {$tt->activity}\n" .
                    "Class: {$facultyName}\n" .
                    "Group: {$group}\n" .
                    "Time: {$startTime} – " . substr($tt->time_end, 0, 5) . "\n" .
                    "Venue: {$venue}";

            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->toToken($lecturer->fcm_token);

            try {
                $messaging->send($message);

                $this->info("Reminder sent to {$lecturer->name} for {$tt->course_code} at {$startTime}");
                Log::info("Lecturer reminder sent: {$lecturer->name} ({$lecturer->id}) – {$tt->course_code} @ {$startTime}");
            } catch (InvalidMessage | NotFound $e) {
                // Invalid or unregistered token → clear it
                $lecturer->update(['fcm_token' => null]);
                Log::info("Cleared invalid FCM token for lecturer {$lecturer->id} ({$lecturer->name})");
                $this->warn("Cleared invalid FCM token for lecturer {$lecturer->name}");
            } catch (MessagingException $e) {
                Log::warning("Failed to send reminder to lecturer {$lecturer->id}: " . $e->getMessage());
                $this->error("Messaging error for {$lecturer->name}: " . $e->getMessage());
            } catch (\Exception $e) {
                Log::error("Unexpected error sending reminder to lecturer {$lecturer->id}: " . $e->getMessage());
            }
        }
    }
}