<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use App\Notifications\SemesterWelcomeStudentNotification;
use App\Notifications\SemesterWelcomeLecturerNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as LaravelNotification;

class SendSemesterWelcomeEmails extends Command
{
    protected $signature = 'semester:welcome-emails';
    protected $description = 'Send semester welcome emails using Laravel Notifications with detailed logging';

    public function handle()
    {
        $this->info('📧 Starting semester welcome email notifications...');
        Log::info('=== Semester Welcome Emails Started ===');

        $this->sendStudentEmails();
        $this->sendLecturerEmails();

        $this->info('✅ Semester welcome emails process completed.');
        Log::info('=== Semester Welcome Emails Completed ===');
    }

    private function sendStudentEmails(): void
    {
        $students = Student::where('status', 'Active')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('id', 'first_name', 'email')
            ->get();

        $this->info("Found {$students->count()} students with email addresses.");
        Log::info("Student email count: {$students->count()}");

        if ($students->isEmpty()) {
            return;
        }

        $totalSent = 0;
        $totalFailed = 0;

        $students->chunk(50)->each(function ($chunk) use (&$totalSent, &$totalFailed) {
            foreach ($chunk as $student) {
                try {
                    LaravelNotification::route('mail', $student->email)
                        ->notify(new SemesterWelcomeStudentNotification($student));

                    $totalSent++;
                    $message = "Email successfully sent to student: {$student->first_name} ({$student->email})";
                    
                    $this->info("✅ " . $message);
                    Log::info($message, ['student_id' => $student->id, 'email' => $student->email]);

                } catch (\Exception $e) {
                    $totalFailed++;
                    $message = "Failed to send email to {$student->first_name} ({$student->email})";
                    
                    $this->error("❌ " . $message);
                    Log::error($message, [
                        'student_id' => $student->id,
                        'email'      => $student->email,
                        'error'      => $e->getMessage(),
                        'trace'      => $e->getTraceAsString()
                    ]);
                }
            }
            sleep(1); // Prevent rate limiting
        });

        $this->info("📊 Student Emails Summary: Sent = {$totalSent} | Failed = {$totalFailed}");
        Log::info("Student email summary", ['sent' => $totalSent, 'failed' => $totalFailed]);
    }

    private function sendLecturerEmails(): void
    {
        $lecturers = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->where('status', 'active')
            ->select('id', 'name', 'email')
            ->get();

        $this->info("Found {$lecturers->count()} lecturers/staff with email addresses.");
        Log::info("Lecturer email count: {$lecturers->count()}");

        if ($lecturers->isEmpty()) {
            return;
        }

        $totalSent = 0;
        $totalFailed = 0;

        $lecturers->chunk(50)->each(function ($chunk) use (&$totalSent, &$totalFailed) {
            foreach ($chunk as $lecturer) {
                try {
                    LaravelNotification::route('mail', $lecturer->email)
                        ->notify(new SemesterWelcomeLecturerNotification($lecturer));

                    $totalSent++;
                    $message = "Email successfully sent to lecturer: {$lecturer->name} ({$lecturer->email})";
                    
                    $this->info("✅ " . $message);
                    Log::info($message, ['lecturer_id' => $lecturer->id, 'email' => $lecturer->email]);

                } catch (\Exception $e) {
                    $totalFailed++;
                    $message = "Failed to send email to {$lecturer->name} ({$lecturer->email})";
                    
                    $this->error("❌ " . $message);
                    Log::error($message, [
                        'lecturer_id' => $lecturer->id,
                        'email'       => $lecturer->email,
                        'error'       => $e->getMessage(),
                        'trace'       => $e->getTraceAsString()
                    ]);
                }
            }
            sleep(1);
        });

        $this->info("📊 Lecturer Emails Summary: Sent = {$totalSent} | Failed = {$totalFailed}");
        Log::info("Lecturer email summary", ['sent' => $totalSent, 'failed' => $totalFailed]);
    }
}