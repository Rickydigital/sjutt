<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Timetable;
use App\Models\ExaminationTimetable;
use App\Models\Student;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendTimetableNotifications extends Command
{
    protected $signature = 'timetables:notify';
    protected $description = 'Send notifications for upcoming lecture and examination timetables';

    public function handle()
    {
        $now = Carbon::now('Africa/Dar_es_Salaam');
        $this->info("Current time: " . $now->toDateTimeString());

        // Skip notifications on Sundays
        if ($now->isSunday()) {
            $this->info("Today is Sunday, skipping lecture notifications");
            Log::info("Skipped lecture notifications on Sunday: " . $now->toDateTimeString());
            return; // Still process exams if needed, or exit entirely
        }

        $credentials = config('firebase.credentials');
        if (!$credentials || !file_exists($credentials)) {
            $this->error("Firebase credentials invalid or file not found: " . ($credentials ?? 'null'));
            Log::error("Firebase credentials invalid or file not found: " . ($credentials ?? 'null'));
            return;
        }

        $factory = (new Factory)->withServiceAccount($credentials);
        $messaging = $factory->createMessaging();

        // 1. Lecture Timetables
        $todayDay = $now->format('l'); // e.g., "Monday"
        $currentTime = $now->format('H:i:s'); // e.g., "08:31:00"
        $upcomingTime = $now->copy()->addMinutes(30)->format('H:i:s'); // e.g., "09:01:00"

        $upcomingTimetables = Timetable::where('day', $todayDay)
            ->where('time_start', '>', $currentTime)
            ->where('time_start', '<', $upcomingTime)
            ->get();
        $this->info("Found " . $upcomingTimetables->count() . " upcoming lecture timetables for $todayDay");

        foreach ($upcomingTimetables as $timetable) {
            $this->info("Processing timetable {$timetable->id}: {$timetable->course_code} at {$timetable->time_start}");
            Log::info("Timetable details: ID={$timetable->id}, Course={$timetable->course_code}, Start={$timetable->time_start}, Venue={$timetable->venue->name}");
            
            $students = Student::where('faculty_id', $timetable->faculty_id)
                ->where('year_of_study', $timetable->year_id)
                ->whereNotNull('fcm_token')
                ->get();
            $this->info("Found " . $students->count() . " students for timetable {$timetable->id}");

            foreach ($students as $student) {
                $this->info("Attempting to notify student {$student->id} with token {$student->fcm_token}");
                $message = CloudMessage::withTarget('token', $student->fcm_token)
                    ->withNotification([
                        'title' => 'Upcoming Lecture',
                        'body' => "{$timetable->course_code} - {$timetable->activity} at " . Carbon::parse($timetable->time_start)->format('H:i') . " in Venue {$timetable->venue->name}",
                    ]);
                try {
                    $messaging->send($message);
                    $this->info("Lecture notification sent to student {$student->id} for timetable {$timetable->id}");
                    Log::info("Sent notification to student {$student->id}: {$timetable->course_code} at {$timetable->time_start}");
                } catch (\Exception $e) {
                    $this->error("Failed to send lecture notification to student {$student->id}: {$e->getMessage()}");
                    Log::error("Lecture notification error for student {$student->id}: {$e->getMessage()}");
                }
            }
        }

        // 2. Examination Timetables
        $upcomingExams = ExaminationTimetable::where('exam_date', '>=', $now->toDateString())->get();
        $this->info("Found " . $upcomingExams->count() . " upcoming exams");

        foreach ($upcomingExams as $exam) {
            $examDateTime = Carbon::parse("{$exam->exam_date} {$exam->start_time}", 'Africa/Dar_es_Salaam');
            $oneDayBefore = $examDateTime->copy()->subDay();
            $oneHourBefore = $examDateTime->copy()->subHour();
            $this->info("Processing exam {$exam->id}: {$exam->course_code} on {$exam->exam_date} at {$exam->start_time}");

            $students = Student::where('faculty_id', $exam->faculty_id)
                ->where('year_of_study', $exam->year_id)
                ->whereNotNull('fcm_token')
                ->get();
            $this->info("Found " . $students->count() . " students for exam {$exam->id}");

            foreach ($students as $student) {
                // 1 Day Before
                if ($oneDayBefore->isToday() && $now->diffInMinutes($oneDayBefore, false) <= 60 && $now->isBefore($oneDayBefore)) {
                    $message = CloudMessage::withTarget('token', $student->fcm_token)
                        ->withNotification([
                            'title' => 'Exam Reminder: 1 Day Away',
                            'body' => "{$exam->course_code} exam is tomorrow, {$exam->exam_date}, at {$exam->start_time} in Venue {$exam->venue->name}",
                        ]);
                    try {
                        $messaging->send($message);
                        $this->info("1-day exam notification sent to student {$student->id} for exam {$exam->id}");
                        Log::info("Sent 1-day exam notification to student {$student->id}: {$exam->course_code}");
                    } catch (\Exception $e) {
                        $this->error("Failed to send 1-day exam notification to student {$student->id}: {$e->getMessage()}");
                        Log::error("1-day exam notification error for student {$student->id}: {$e->getMessage()}");
                    }
                }

                // 1 Hour Before
                if ($examDateTime->isToday() && $now->diffInMinutes($oneHourBefore, false) <= 60 && $now->isBefore($oneHourBefore)) {
                    $message = CloudMessage::withTarget('token', $student->fcm_token)
                        ->withNotification([
                            'title' => 'Exam Reminder: 1 Hour Away',
                            'body' => "{$exam->course_code} exam starts at {$exam->start_time} today in Venue {$exam->venue->name}",
                        ]);
                    try {
                        $messaging->send($message);
                        $this->info("1-hour exam notification sent to student {$student->id} for exam {$exam->id}");
                        Log::info("Sent 1-hour exam notification to student {$student->id}: {$exam->course_code}");
                    } catch (\Exception $e) {
                        $this->error("Failed to send 1-hour exam notification to student {$student->id}: {$e->getMessage()}");
                        Log::error("1-hour exam notification error for student {$student->id}: {$e->getMessage()}");
                    }
                }
            }
        }
    }
}