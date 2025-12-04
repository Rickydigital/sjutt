<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Timetable;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendTimetableNotifications extends Command
{
    protected $signature = 'timetable:notify';
    protected $description = 'Send FCM notifications 30 minutes before class starts';

    public function handle()
    {
        $now = Carbon::now('Africa/Dar_es_Salaam');
        $this->info("Running timetable:notify at " . $now->format('Y-m-d H:i:s'));

        $today = $now->format('l');
        $currentTime = $now->format('H:i:s');
        $in30Minutes = $now->copy()->addMinutes(30)->format('H:i:s');

        // Find ALL classes starting in the next 30 minutes
        $sessions = Timetable::with(['course', 'venue', 'lecturer', 'faculty'])
            ->where('day', $today)
            ->whereTime('time_start', '>', $currentTime)
            ->whereTime('time_start', '<=', $in30Minutes)
            ->currentSemester()
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('No classes starting in the next 30 minutes.');
            return 0;
        }

        $this->info("Found {$sessions->count()} class(es) starting soon!");

        $messaging = Firebase::messaging();

        foreach ($sessions as $session) {
            $start = Carbon::parse($session->time_start)->format('H:i');
            $courseName = $session->course?->course_name ?? $session->course_code;
            $venue = $session->venue?->name ?? 'TBA';
            $lecturer = $session->lecturer?->name ?? 'Not assigned';
            $group = $session->group_selection;

            $title = "Class in 30 mins: {$courseName}";

            // Build body — add group if exists
            $bodyLines = [
                $session->activity,
                "{$start} - {$session->time_end}",
                "Room: {$venue}",
                "Lecturer: {$lecturer}",
            ];

            if ($group && $group !== 'All' && !empty(trim($group))) {
                array_unshift($bodyLines, "Group: {$group}"); // Show group at the top
            }

            $body = implode("\n", $bodyLines);

            // Send to ALL active students in this faculty
            $tokens = Student::where('faculty_id', $session->faculty_id)
                ->where('status', 'active')
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->chunk(500);

            $totalSent = 0;

            foreach ($tokens as $tokenChunk) {
                $notification = Notification::create($title, $body);
                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withData([
                        'type' => 'timetable_reminder',
                        'course_code' => $session->course_code,
                        'time_start' => $session->time_start,
                        'venue' => $venue,
                        'group' => $group ?? '',
                    ])
                    ->withDefaultSounds();

                try {
                    $report = $messaging->sendMulticast($message, $tokenChunk->toArray());
                    $totalSent += $report->successes()->count();

                    foreach ($report->invalidTokens() as $invalid) {
                        Log::warning("Invalid FCM token: {$invalid}");
                    }
                } catch (\Exception $e) {
                    Log::error("FCM Error: " . $e->getMessage());
                }
            }

            $groupText = $group && $group !== 'All' ? " [{$group}]" : '';
            $this->info("Reminder sent: {$courseName} at {$start}{$groupText} → {$totalSent} students");
        }

        $this->info('All timetable notifications sent successfully!');
        return 0;
    }
}