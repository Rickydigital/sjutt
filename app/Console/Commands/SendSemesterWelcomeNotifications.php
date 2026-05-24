<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendSemesterWelcomeNotifications extends Command
{
    protected $signature = 'semester:welcome';
    protected $description = 'Send personalized semester welcome push notifications (Firebase only)';

    public function handle(): void
    {
        $this->info('🚀 Starting semester welcome push notifications...');

        $credentialsPath = storage_path('app/firebase-credentials.json');

        if (!file_exists($credentialsPath)) {
            $this->error('Firebase credentials file not found.');
            Log::error('Semester welcome failed: Firebase credentials missing.');
            return;
        }

        try {
            $messaging = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->createMessaging();
        } catch (\Exception $e) {
            $this->error('Failed to initialize Firebase: ' . $e->getMessage());
            Log::error('Semester welcome Firebase init failed: ' . $e->getMessage());
            return;
        }

        $this->sendStudentNotifications($messaging);
        $this->sendLecturerNotifications($messaging);

        $this->info('✅ Semester welcome push notifications completed.');
        Log::info('Semester welcome notifications completed successfully.');
    }

    private function sendStudentNotifications($messaging): void
    {
        $students = Student::where('status', 'Active')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->select('id', 'first_name', 'fcm_token')
            ->get();

        $this->info("Found {$students->count()} students with FCM tokens.");

        if ($students->isEmpty()) return;

        $title = 'Welcome to the New Semester';
        $totalSent = 0;
        $cleared = 0;

        $students->chunk(400)->each(function ($chunk) use ($messaging, &$totalSent, &$cleared, $title) {
            $messages = [];

            foreach ($chunk as $student) {
                $body = "Dear {$student->first_name}, welcome back to St John's University of Tanzania.\n\n" .
                        "A new semester means new opportunities and growth.\n\n" .
                        "Stay focused, attend your classes, work hard, and believe in yourself.\n\n" .
                        "We wish you a successful semester!\n\n" .
                        "— St John's University of Tanzania";

                $notification = Notification::create($title, $body);

                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withDefaultSounds()
                    ->withData(['type' => 'semester_welcome'])
                    ->toToken($student->fcm_token);

                $messages[] = $message;
            }

            try {
                $report = $messaging->sendAll($messages);
                $totalSent += $report->successes()->count();

                foreach ($report->invalidTokens() as $token) {
                    $student = $chunk->firstWhere('fcm_token', $token);
                    if ($student) {
                        $student->update(['fcm_token' => null]);
                        $cleared++;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Student chunk failed: " . $e->getMessage());
            }
        });

        $this->info("✅ Sent to {$totalSent} students.");
        if ($cleared > 0) $this->warn("Cleared {$cleared} invalid tokens.");
    }

    private function sendLecturerNotifications($messaging): void
    {
        $lecturers = User::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->where('status', 'active')
            ->select('id', 'name', 'fcm_token')
            ->get();

        $this->info("Found {$lecturers->count()} lecturers with FCM tokens.");

        if ($lecturers->isEmpty()) return;

        $title = 'Welcome to the New Academic Semester';
        $totalSent = 0;
        $cleared = 0;

        $lecturers->chunk(400)->each(function ($chunk) use ($messaging, &$totalSent, &$cleared, $title) {
            $messages = [];

            foreach ($chunk as $lecturer) {
                $body = "Dear {$lecturer->name}, welcome to a new semester at St John's University of Tanzania.\n\n" .
                        "Thank you for your dedication and hard work.\n\n" .
                        "We wish you a productive and successful semester.\n\n" .
                        "— St John's University of Tanzania";

                $notification = Notification::create($title, $body);

                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withDefaultSounds()
                    ->withData(['type' => 'semester_welcome'])
                    ->toToken($lecturer->fcm_token);

                $messages[] = $message;
            }

            try {
                $report = $messaging->sendAll($messages);
                $totalSent += $report->successes()->count();

                foreach ($report->invalidTokens() as $token) {
                    $lecturer = $chunk->firstWhere('fcm_token', $token);
                    if ($lecturer) {
                        $lecturer->update(['fcm_token' => null]);
                        $cleared++;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Lecturer chunk failed: " . $e->getMessage());
            }
        });

        $this->info("✅ Sent to {$totalSent} lecturers/staff.");
        if ($cleared > 0) $this->warn("Cleared {$cleared} invalid tokens.");
    }
}