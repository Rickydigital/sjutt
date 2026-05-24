<?php

namespace App\Console\Commands;

use App\Mail\SemesterWelcomeMail;
use App\Mail\LecturerSemesterWelcomeMail;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSemesterWelcomeEmails extends Command
{
    protected $signature = 'semester:welcome-emails';
    protected $description = 'Send semester welcome emails to students and lecturers (Email Only)';

    public function handle(): void
    {
        $this->info('📧 Starting semester welcome email notifications...');

        $this->sendStudentEmails();
        $this->sendLecturerEmails();

        $this->info('✅ Semester welcome emails completed.');
        Log::info('Semester welcome emails completed successfully.');
    }

    /**
     * STUDENTS - Email Notifications
     */
    private function sendStudentEmails(): void
    {
        $students = Student::where('status', 'Active')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('id', 'first_name', 'email')
            ->get();

        $this->info("Found {$students->count()} students with email addresses.");

        if ($students->isEmpty()) {
            return;
        }

        $totalSent = 0;
        $failed = 0;

        $students->chunk(50)->each(function ($chunk) use (&$totalSent, &$failed) {
            foreach ($chunk as $student) {
                try {
                    Mail::to($student->email)
                        ->send(new SemesterWelcomeMail($student));

                    $totalSent++;
                    $this->info("Email sent to student: {$student->first_name} ({$student->email})");
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to send welcome email to student {$student->id} ({$student->email}): " . $e->getMessage());
                }
            }

            // Small delay to avoid email server rate limits
            if ($chunk->count() > 0) {
                sleep(1);
            }
        });

        $this->info("✅ Student welcome emails sent: {$totalSent} | Failed: {$failed}");
    }

    /**
     * LECTURERS / STAFF - Email Notifications
     */
    private function sendLecturerEmails(): void
    {
        $lecturers = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->where('status', 'active')
            ->select('id', 'name', 'email')
            ->get();

        $this->info("Found {$lecturers->count()} lecturers/staff with email addresses.");

        if ($lecturers->isEmpty()) {
            return;
        }

        $totalSent = 0;
        $failed = 0;

        $lecturers->chunk(50)->each(function ($chunk) use (&$totalSent, &$failed) {
            foreach ($chunk as $lecturer) {
                try {
                    Mail::to($lecturer->email)
                        ->send(new LecturerSemesterWelcomeMail($lecturer));

                    $totalSent++;
                    $this->info("Email sent to lecturer: {$lecturer->name} ({$lecturer->email})");
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to send welcome email to lecturer {$lecturer->id} ({$lecturer->email}): " . $e->getMessage());
                }
            }

            sleep(1); // Prevent rate limiting
        });

        $this->info("✅ Lecturer welcome emails sent: {$totalSent} | Failed: {$failed}");
    }
}