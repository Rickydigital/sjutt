<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Timetable;
use App\Models\ExaminationTimetable;
use App\Models\Student;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\Messaging\InvalidMessage; // For malformed/invalid tokens
use Kreait\Firebase\Exception\Messaging\NotFound;       // For expired/unregistered tokens
use Kreait\Firebase\Exception\MessagingException;      // Base for other messaging errors
use Illuminate\Support\Facades\Log;

class SendTimetableNotifications extends Command
{
    protected $signature = 'timetables:notify';
    protected $description = 'Send notifications for upcoming lectures and examinations';

    public function handle()
    {
        $now = Carbon::now('Africa/Dar_es_Salaam');
        $this->info("Running at {$now}");

        $credentials = config('firebase.credentials');
        if (!$credentials || !file_exists($credentials)) {
            $this->error("Firebase credentials invalid or file not found: " . ($credentials ?? 'null'));
            Log::error("Firebase credentials invalid or file not found: " . ($credentials ?? 'null'));
            return;
        }

        $factory = (new Factory)->withServiceAccount($credentials);
        $messaging = $factory->createMessaging();

        // ----------------------------------------------------------------
        // 1. LECTURE TIMETABLES (skip on Sunday)
        // ----------------------------------------------------------------
        if (! $now->isSunday()) {
            $this->sendLectureNotifications($messaging, $now);
        } else {
            $this->info('Sunday – lecture notifications skipped');
            Log::info('Skipped lecture notifications on Sunday: ' . $now->toDateTimeString());
        }

        // ----------------------------------------------------------------
        // 2. EXAMINATION TIMETABLES
        // ----------------------------------------------------------------
        $this->sendExamNotifications($messaging, $now);
    }

    private function sendLectureNotifications($messaging, Carbon $now)
{
    $day         = $now->format('l');
    $current     = $now->format('H:i:s');
    $in30Minutes = $now->copy()->addMinutes(30)->format('H:i:s');

    $timetables = Timetable::where('day', $day)
        ->where('time_start', '>', $current)
        ->where('time_start', '<=', $in30Minutes)
        ->with(['venue', 'lecturer', 'faculty']) // Eager load needed relations
        ->get();

    $this->info("Found {$timetables->count()} upcoming lecture timetables for {$day}");
    Log::info("Found {$timetables->count()} upcoming lecture timetables for {$day}");

    foreach ($timetables as $tt) {
        $students = Student::where('faculty_id', $tt->faculty_id)
            ->where('status', 'Active')
            ->whereNotNull('fcm_token')
            ->get();

        $total    = $students->count();
        $sent     = 0;
        $failed   = 0;
        $cleared  = 0;

        // Prepare common parts
        $time     = Carbon::parse($tt->time_start)->format('H:i');
        $venue    = $tt->venue?->name ?? 'TBD';
        $lecturerName = $tt->lecturer?->name ?? 'Not Assigned';
        $group    = $tt->group_selection ? "Group {$tt->group_selection}" : 'All Groups';
        $facultyName = $tt->faculty?->name ?? 'Unknown Faculty';

        // === 1. STUDENT NOTIFICATION ===
        $studentBody =  "{$tt->course_code} - {$tt->activity}\n" .
               "Lecturer: {$lecturerName}\n" .
               "Group: {$group}\n" .
               "Time: {$time}\n" .
               "Venue: {$venue}";

        $studentNotification = Notification::create(
            'Upcoming Lecture',
            $studentBody
        );

        foreach ($students as $student) {
            $message = CloudMessage::new()
                ->withNotification($studentNotification)
                ->toToken($student->fcm_token);

            try {
                $messaging->send($message);
                $sent++;
            } catch (InvalidMessage | NotFound $e) {
                $this->clearFcmToken($student);
                $cleared++;
                $failed++;
            } catch (MessagingException $e) {
                $failed++;
                Log::warning("Lecture notification failed (student {$student->id}): {$e->getMessage()}");
            }
        }

        // === 2. LECTURER NOTIFICATION ===
        $lecturerSent = false;
        $lecturerCleared = false;

        if ($tt->lecturer && $tt->lecturer->fcm_token) {
            $lecturerBody = "Teaching Reminder: {$tt->course_code} - {$tt->activity}\n" .
                            "Faculty: {$facultyName}\n" .
                            "Group: {$group}\n" .
                            "Today at {$time} in {$venue}";

            $lecturerNotification = Notification::create(
                'Teaching Reminder',
                $lecturerBody
            );

            $message = CloudMessage::new()
                ->withNotification($lecturerNotification)
                ->toToken($tt->lecturer->fcm_token);

            try {
                $messaging->send($message);
                $lecturerSent = true;
                $this->info("Lecturer notification sent to {$tt->lecturer->name}");
                Log::info("Lecturer reminder sent: {$tt->lecturer->name} for {$tt->course_code}");
            } catch (InvalidMessage | NotFound $e) {
                $tt->lecturer->update(['fcm_token' => null]);
                $lecturerCleared = true;
                Log::info("Cleared invalid FCM token for lecturer {$tt->lecturer->id}");
            } catch (\Exception $e) {
                Log::warning("Failed to send lecturer reminder: " . $e->getMessage());
            }
        }

        // === SUMMARY LOG ===
        $summary = "Lecture {$tt->course_code} @ {$time} → Students: Total {$total}, Sent {$sent}, Failed {$failed}";
        if ($cleared) $summary .= " (Cleared {$cleared} invalid student tokens)";

        if ($tt->lecturer) {
            $summary .= " | Lecturer: " . ($lecturerSent ? 'Sent' : ($lecturerCleared ? 'Token cleared' : 'No token/Skipped'));
        } else {
            $summary .= " | Lecturer: Not assigned";
        }

        $this->info($summary);
        Log::info($summary);
    }
}

    private function sendExamNotifications($messaging, Carbon $now)
    {
        $exams = ExaminationTimetable::where('exam_date', '>=', $now->toDateString())
            ->with('venue')
            ->get();

        $this->info("Found {$exams->count()} upcoming exams");
        Log::info("Found {$exams->count()} upcoming exams");

        foreach ($exams as $exam) {
            $examDt      = Carbon::parse("{$exam->exam_date} {$exam->start_time}", 'Africa/Dar_es_Salaam');
            $oneDayBefore = $examDt->copy()->subDay();
            $oneHourBefore = $examDt->copy()->subHour();

            $students = Student::where('faculty_id', $exam->faculty_id)
                ->whereNotNull('fcm_token')
                ->get();

            $total = $students->count();

            // 1-day reminder (within 1 hour before the reminder time)
            if ($oneDayBefore->isToday() && $now->diffInMinutes($oneDayBefore, false) <= 60 && $now->isBefore($oneDayBefore)) {
                $this->sendExamBatch(
                    $messaging,
                    $students,
                    $exam,
                    $total,
                    'Exam Reminder: 1 Day Away',
                    "{$exam->course_code} exam is tomorrow, {$exam->exam_date}, at {$exam->start_time} in {$exam->venue->name}"
                );
            }

            // 1-hour reminder (within 1 hour before the reminder time)
            if ($examDt->isToday() && $now->diffInMinutes($oneHourBefore, false) <= 60 && $now->isBefore($oneHourBefore)) {
                $this->sendExamBatch(
                    $messaging,
                    $students,
                    $exam,
                    $total,
                    'Exam Reminder: 1 Hour Away',
                    "{$exam->course_code} exam starts at {$exam->start_time} today in {$exam->venue->name}"
                );
            }
        }
    }

    private function sendExamBatch($messaging, $students, $exam, $total, $title, $body)
    {
        $sent    = 0;
        $failed  = 0;
        $cleared = 0;

        $notification = Notification::create($title, $body);

        foreach ($students as $student) {
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->toToken($student->fcm_token);

            try {
                $messaging->send($message);
                $sent++;
            } catch (InvalidMessage $e) {
                // Malformed/invalid token → clear it
                $this->clearFcmToken($student);
                $cleared++;
                $failed++;
                Log::warning("Exam notification failed (invalid token, student {$student->id}): {$e->getMessage()}");
            } catch (NotFound $e) {
                // Unregistered/expired token → clear it
                $this->clearFcmToken($student);
                $cleared++;
                $failed++;
                Log::warning("Exam notification failed (not found, student {$student->id}): {$e->getMessage()}");
            } catch (MessagingException $e) {
                $failed++;
                Log::warning("Exam notification failed (student {$student->id}): {$e->getMessage()}");
            } catch (\Exception $e) {
                $failed++;
                Log::error("Unexpected exam notification error (student {$student->id}): {$e->getMessage()}");
            }
        }

        $summary = "Exam {$exam->course_code} ({$title}) → Total: {$total}, Sent: {$sent}, Failed: {$failed}";
        if ($cleared) $summary .= " (Cleared {$cleared} invalid tokens)";

        $this->info($summary);
        Log::info($summary);
    }

    private function clearFcmToken(Student $student): void
    {
        $student->fcm_token = null;
        $student->saveQuietly(); 
        Log::info("Cleared invalid FCM token for student {$student->id}");
    }
}