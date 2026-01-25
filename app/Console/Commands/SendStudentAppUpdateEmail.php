<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Mail\StudentAppUpdateNotification;
use Illuminate\Support\Facades\Mail;

class SendStudentAppUpdateEmail extends Command
{
    protected $signature = 'email:student-app-update';
    protected $description = 'Send email to all students about the latest SJUT Mobile App update (v1.1.0)';

    public function handle()
    {
        $students = Student::whereNotNull('email')
            ->where('email', '!=', '')
            ->where('status', 'Active')
            ->get();

        if ($students->isEmpty()) {
            $this->info('No active students found with valid email addresses.');
            return 0;
        }

        $this->info("Preparing to send app update notification to {$students->count()} students...");

        $sent = 0;
        $failed = 0;

        foreach ($students as $student) {
            $fullName = trim("{$student->first_name} {$student->last_name}");

            try {
                Mail::to($student->email, $fullName)
                    ->queue(new StudentAppUpdateNotification($student)); // Queued for better performance

                $sent++;
                $this->line("Queued → {$fullName} <{$student->email}>");

            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed → {$fullName} ({$student->email}): " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Student app update campaign queued successfully!");
        $this->info("Queued: {$sent}");
        if ($failed > 0) {
            $this->warn("Failed: {$failed}");
        }

        $this->info("Run 'php artisan queue:work' to process the queued emails.");

        return 0;
    }
}