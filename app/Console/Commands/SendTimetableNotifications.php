<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Timetable;
use App\Models\ExaminationTimetable;
use App\Models\Student;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Log;

class SendTimetableNotifications extends Command
{
    protected $signature = 'timetables:notify';
    protected $description = 'Send notifications for upcoming lecture and examination timetables';

    public function handle()
    {
        $now = now('Africa/Dar_es_Salaam');
        $this->info("Current time: " . $now->toDateTimeString());

        $envValue = env('FIREBASE_CREDENTIALS');
        $configValue = config('firebase.credentials');
        $this->info("ENV FIREBASE_CREDENTIALS: " . ($envValue ?? 'null'));
        $this->info("Config firebase.credentials: " . ($configValue ?? 'null'));

        $credentials = config('firebase.credentials');
        if (!$credentials || !file_exists($credentials)) {
            $this->error("Firebase credentials invalid or file not found: " . ($credentials ?? 'null'));
            Log::error("Firebase credentials invalid or file not found: " . ($credentials ?? 'null'));
            return;
        }

        $factory = (new Factory)->withServiceAccount($credentials);
        $messaging = $factory->createMessaging();

        // 1. Lecture Timetables
        $upcomingTimetables = Timetable::where('time_start', '>', $now)
            ->where('time_start', '<', $now->copy()->addMinutes(30))
            ->get();
        $this->info("Found " . $upcomingTimetables->count() . " upcoming lecture timetables");

        foreach ($upcomingTimetables as $timetable) {
            $this->info("Processing timetable {$timetable->id}: {$timetable->course_code} at {$timetable->time_start}");
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
                        'body' => "{$timetable->course_code} - {$timetable->activity} at {$timetable->time_start->format('H:i')} in Venue {$timetable->venue_id}",
                    ]);
                try {
                    $messaging->send($message);
                    $this->info("Lecture notification sent to student {$student->id} for timetable {$timetable->id}");
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
            $examDateTime = \Carbon\Carbon::parse("{$exam->exam_date} {$exam->start_time}", 'Africa/Dar_es_Salaam');
            $oneDayBefore = $examDateTime->copy()->subDay();
            $oneHourBefore = $examDateTime->copy()->subHour();
            $this->info("Processing exam {$exam->id}: {$exam->course_code} on {$exam->exam_date} at {$exam->start_time}");
            $this->info("1-day before: {$oneDayBefore}, 1-hour before: {$oneHourBefore}");

            $students = Student::where('faculty_id', $exam->faculty_id)
                ->where('year_of_study', $exam->year_id)
                ->whereNotNull('fcm_token')
                ->get();
            $this->info("Found " . $students->count() . " students for exam {$exam->id}");

            foreach ($students as $student) {
                $this->info("Checking student {$student->id} with token {$student->fcm_token}");
                // 1 Day Before
                if ($oneDayBefore->isToday() && $now->diffInMinutes($oneDayBefore, false) <= 60 && $now->isBefore($oneDayBefore)) {  // 60 minutes
                    $this->info("1-day condition met for exam {$exam->id} at {$oneDayBefore}");
                    $message = CloudMessage::withTarget('token', $student->fcm_token)
                        ->withNotification([
                            'title' => 'Exam Reminder: 1 Day Away',
                            'body' => "{$exam->course_code} exam is tomorrow, {$exam->exam_date}, at {$exam->start_time} in Venue {$exam->venue_id}",
                        ]);
                    try {
                        $messaging->send($message);
                        $this->info("1-day exam notification sent to student {$student->id} for exam {$exam->id}");
                    } catch (\Exception $e) {
                        $this->error("Failed to send 1-day exam notification to student {$student->id}: {$e->getMessage()}");
                        Log::error("1-day exam notification error for student {$student->id}: {$e->getMessage()}");
                    }
                } else {
                    $this->info("1-day condition not met for exam {$exam->id}: Today={$now->toDateString()}, Target={$oneDayBefore}");
                }

                // 1 Hour Before
                if ($examDateTime->isToday() && $now->diffInMinutes($oneHourBefore, false) <= 60 && $now->isBefore($oneHourBefore)) {  // 60 minutes
                    $this->info("1-hour condition met for exam {$exam->id} at {$oneHourBefore}");
                    $message = CloudMessage::withTarget('token', $student->fcm_token)
                        ->withNotification([
                            'title' => 'Exam Reminder: 1 Hour Away',
                            'body' => "{$exam->course_code} exam starts at {$exam->start_time} today in Venue {$exam->venue_id}",
                        ]);
                    try {
                        $messaging->send($message);
                        $this->info("1-hour exam notification sent to student {$student->id} for exam {$exam->id}");
                    } catch (\Exception $e) {
                        $this->error("Failed to send 1-hour exam notification to student {$student->id}: {$e->getMessage()}");
                        Log::error("1-hour exam notification error for student {$student->id}: {$e->getMessage()}");
                    }
                } else {
                    $this->info("1-hour condition not met for exam {$exam->id}: Today={$now->toDateString()}, Target={$oneHourBefore}");
                }
            }
        }
    }
}