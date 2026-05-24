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
    protected $description = 'Send semester welcome emails using Laravel Notifications';

    public function handle()
    {
        $this->info('📧 Starting semester welcome emails using Notifications...');

        $this->sendStudentEmails();
        $this->sendLecturerEmails();

        $this->info('✅ Semester welcome emails completed.');
        Log::info('Semester welcome emails completed.');
    }

    private function sendStudentEmails()
    {
        $students = Student::where('status', 'Active')
            ->whereNotNull('email')
            ->select('id', 'first_name', 'email')
            ->get();

        $this->info("Found {$students->count()} students with emails.");

        $students->chunk(50)->each(function ($chunk) {
            foreach ($chunk as $student) {
                try {
                    LaravelNotification::route('mail', $student->email)
                        ->notify(new SemesterWelcomeStudentNotification($student));

                    $this->info("Email sent to: {$student->first_name} ({$student->email})");
                } catch (\Exception $e) {
                    Log::error("Student email failed {$student->id}: " . $e->getMessage());
                }
            }
            sleep(1);
        });
    }

    private function sendLecturerEmails()
    {
        $lecturers = User::whereNotNull('email')
            ->where('status', 'active')
            ->select('id', 'name', 'email')
            ->get();

        $this->info("Found {$lecturers->count()} lecturers with emails.");

        $lecturers->chunk(50)->each(function ($chunk) {
            foreach ($chunk as $lecturer) {
                try {
                    LaravelNotification::route('mail', $lecturer->email)
                        ->notify(new SemesterWelcomeLecturerNotification($lecturer));

                    $this->info("Email sent to: {$lecturer->name} ({$lecturer->email})");
                } catch (\Exception $e) {
                    Log::error("Lecturer email failed {$lecturer->id}: " . $e->getMessage());
                }
            }
            sleep(1);
        });
    }
}