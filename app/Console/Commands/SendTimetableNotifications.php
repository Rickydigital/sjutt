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

        // Skip lecture notifications on Sundays (exams can still run)
        if ($now->isSunday()) {
            $this->info("Today is Sunday, skipping lecture notifications");
            Log::info("Skipped lecture notifications on Sunday: " . $now->toDateTimeString());
        }

        $credentials = config('firebase.credentials');
        if (!$credentials || !file_exists($credentials)) {
            $this->error("Firebase credentials invalid or file not found: " . ($credentials ?? 'null'));
            Log::error("Firebase credentials invalid or file not found: " . ($credentials ?? 'null'));
            return;
        }

        $factory = (new Factory)->withServiceAccount($credentials);
        $messaging = $factory->createMessaging();

        // ================================================================
        // 1. LECTURE TIMETABLES
        // ================================================================
        if (! $now->isSunday()) {
            $todayDay     = $now->format('l');               // e.g. "Monday"
            $currentTime  = $now->format('H:i:s');
            $upcomingTime = $now->copy()->addMinutes(30)->format('H:i:s');

            $upcomingTimetables = Timetable::where('day', $todayDay)
                ->where('time_start', '>', $currentTime)
                ->where('time_start', '<', $upcomingTime)
                ->get();

            $this->info("Found {$upcomingTimetables->count()} upcoming lecture timetables for $todayDay");

            foreach ($upcomingTimetables as $timetable) {
                $this->info("Processing timetable {$timetable->id}: {$timetable->course_code} at {$timetable->time_start}");
                Log::info("Timetable ID={$timetable->id}, Course={$timetable->course_code}, Start={$timetable->time_start}, Venue={$timetable->venue->name}");

                // ONLY faculty_id is used
                $students = Student::where('faculty_id', $timetable->faculty_id)
                    ->whereNotNull('fcm_token')
                    ->get();

                $this->info("Found {$students->count()} students for timetable {$timetable->id}");

                foreach ($students as $student) {
                    $message = CloudMessage::withTarget('token', $student->fcm_token)
                        ->withNotification([
                            'title' => 'Upcoming Lecture',
                            'body'  => "{$timetable->course_code} - {$timetable->activity} at "
                                      . Carbon::parse($timetable->time_start)->format('H:i')
                                      . " in {$timetable->venue->name}",
                        ]);

                    try {
                        $messaging->send($message);
                        $this->info("Lecture notification sent to student {$student->id}");
                        Log::info("Sent lecture to {$student->id}: {$timetable->course_code}");
                    } catch (\Exception $e) {
                        $this->error("Failed lecture notification to {$student->id}: {$e->getMessage()}");
                        Log::error("Lecture error for {$student->id}: {$e->getMessage()}");
                    }
                }
            }
        }

        // ================================================================
        // 2. EXAMINATION TIMETABLES
        // ================================================================
        $upcomingExams = ExaminationTimetable::where('exam_date', '>=', $now->toDateString())
            ->get();

        $this->info("Found {$upcomingExams->count()} upcoming exams");

        foreach ($upcomingExams as $exam) {
            $examDateTime   = Carbon::parse("{$exam->exam_date} {$exam->start_time}", 'Africa/Dar_es_Salaam');
            $oneDayBefore   = $examDateTime->copy()->subDay();
            $oneHourBefore  = $examDateTime->copy()->subHour();

            $this->info("Processing exam {$exam->id}: {$exam->course_code} on {$exam->exam_date} at {$exam->start_time}");

            // ONLY faculty_id is used
            $students = Student::where('faculty_id', $exam->faculty_id)
                ->whereNotNull('fcm_token')
                ->get();

            $this->info("Found {$students->count()} students for exam {$exam->id}");

            foreach ($students as $student) {
                // ---- 1 DAY BEFORE ----
                if ($oneDayBefore->isToday()
                    && $now->diffInMinutes($oneDayBefore, false) <= 60
                    && $now->isBefore($oneDayBefore)) {

                    $message = CloudMessage::withTarget('token', $student->fcm_token)
                        ->withNotification([
                            'title' => 'Exam Reminder: 1 Day Away',
                            'body'  => "{$exam->course_code} exam is tomorrow, {$exam->exam_date}, at {$exam->start_time} in {$exam->venue->name}",
                        ]);

                    try {
                        $messaging->send($message);
                        $this->info("1-day exam notification sent to student {$student->id}");
                        Log::info("Sent 1-day exam to {$student->id}: {$exam->course_code}");
                    } catch (\Exception $e) {
                        $this->error("Failed 1-day exam notification: {$e->getMessage()}");
                        Log::error("1-day exam error for {$student->id}: {$e->getMessage()}");
                    }
                }

                // ---- 1 HOUR BEFORE ----
                if ($examDateTime->isToday()
                    && $now->diffInMinutes($oneHourBefore, false) <= 60
                    && $now->isBefore($oneHourBefore)) {

                    $message = CloudMessage::withTarget('token', $student->fcm_token)
                        ->withNotification([
                            'title' => 'Exam Reminder: 1 Hour Away',
                            'body'  => "{$exam->course_code} exam starts at {$exam->start_time} today in {$exam->venue->name}",
                        ]);

                    try {
                        $messaging->send($message);
                        $this->info("1-hour exam notification sent to student {$student->id}");
                        Log::info("Sent 1-hour exam to {$student->id}: {$exam->course_code}");
                    } catch (\Exception $e) {
                        $this->error("Failed 1-hour exam notification: {$e->getMessage()}");
                        Log::error("1-hour exam error for {$student->id}: {$e->getMessage()}");
                    }
                }
            }
        }
    }
}