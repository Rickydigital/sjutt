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
    protected $description = 'Send 12 ULTRA CRAZY & emoji-packed push notifications to students';

    public function handle(): void
    {
        $this->info('🚀 Launching ULTRA CRAZY semester notifications... 🔥😂');

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

        $this->sendCrazyStudentNotifications($messaging);
        $this->sendLecturerNotifications($messaging);

        $this->info('✅ ULTRA CRAZY notifications completed! 🎉🔥');
        Log::info('Ultra crazy semester welcome notifications completed.');
    }

    private function sendCrazyStudentNotifications($messaging): void
    {
        $students = Student::where('status', 'Active')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->select('id', 'first_name', 'fcm_token')
            ->get();

        $this->info("Found {$students->count()} students → Dropping 12 crazy bombs each! 💣");

        $crazyMessages = [
            ['title' => '🔥 YO! NEW SEMESTER LOADED 🚀',          'body' => "Welcome back to the madness! Open SJUT App daily for timetable & hot news! 📱"],
            ['title' => '🆘 TROUBLE? COME HERE 😂',               'body' => "Any problem? Stress, money, results? Open app and chat with Dean of Students! 🤝"],
            ['title' => '😂 BIOMETRIC DON COME!',                 'body' => "My guy, 'my friend signed for me' season is officially over! 😂 Biometric attendance don land!"],
            ['title' => '⏰ WE GO DISTURB YOU!',                  'body' => "We will remind you like your village people! Right time. Right venue. No stories! 🏃‍♂️"],
            ['title' => '🤖 SJUT DON ENTER 2026!',                'body' => "Real-time class tracking + smart alerts activated! You dey future already 🔥🤖"],
            ['title' => '🏆 DEAN LIST DEY CALL YOU!',             'body' => "Your name is about to blow on Dean's List this semester! Grind hard! 💪"],
            ['title' => '🍔 FREE FOOD DEY CALL!',                 'body' => "If you see FREE FOOD on the app... Oya run like flash!!! 😂🍟"],
            ['title' => '📚 SMALL SMALL NA GOLD',                 'body' => "One small reading every day > Night class suicide! Plan like a boss 📖"],
            ['title' => '🎉 CAMPUS DEY HOT!',                     'body' => "Clubs, sports, events, fine people dey load! Don't sleep! 🎊😎"],
            ['title' => '😎 COOL KIDS ONLY!',                     'body' => "Only real cool students open SJUT App every day! You be cool kid? 😂"],
            ['title' => '🌟 THIS SEMESTER NA YOURS!',             'body' => "This semester you go shine! Work hard, pray hard, shock everyone! 🔥"],
            ['title' => '🛡️ APP GO DISTURB YOU!',                 'body' => "Warning: This app will disturb you with useful info! No mute us ooo 😂❤️"],
        ];

        $totalSent = 0;
        $cleared = 0;

        $students->chunk(300)->each(function ($chunk) use ($messaging, &$totalSent, &$cleared, $crazyMessages) {
            foreach ($crazyMessages as $msg) {
                $messages = [];

                foreach ($chunk as $student) {
                    $body = "Dear {$student->first_name}, " . $msg['body'];

                    $notification = Notification::create($msg['title'], $body);

                    $message = CloudMessage::new()
                        ->withNotification($notification)
                        ->withDefaultSounds()
                        ->withData(['type' => 'semester_crazy'])
                        ->toToken($student->fcm_token);

                    $messages[] = $message;
                }

                try {
                    $report = $messaging->sendAll($messages);
                    $totalSent += $report->successes()->count();

                    foreach ($report->invalidTokens() as $invalidToken) {
                        $student = $chunk->firstWhere('fcm_token', $invalidToken);
                        if ($student) {
                            $student->update(['fcm_token' => null]);
                            $cleared++;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Crazy message failed: " . $e->getMessage());
                }

                sleep(1);
            }
        });

        $this->info("🎉 Sent {$totalSent} ULTRA CRAZY notifications! 🔥");
        if ($cleared > 0) $this->warn("Cleared {$cleared} invalid tokens.");
    }

    private function sendLecturerNotifications($messaging): void
    {
        $lecturers = User::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->where('status', 'active')
            ->select('id', 'name', 'fcm_token')
            ->get();

        if ($lecturers->isEmpty()) return;

        $title = '📌 Staff Quick Note';
        $body  = "Please encourage students to open the SJUT App regularly for timetable & announcements.\nThank you! 🙏";

        $totalSent = 0;

        $lecturers->chunk(400)->each(function ($chunk) use ($messaging, &$totalSent, $title, $body) {
            $messages = [];
            foreach ($chunk as $lecturer) {
                $notification = Notification::create($title, $body);
                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withDefaultSounds()
                    ->toToken($lecturer->fcm_token);
                $messages[] = $message;
            }

            try {
                $report = $messaging->sendAll($messages);
                $totalSent += $report->successes()->count();
            } catch (\Exception $e) {
                Log::error("Staff reminder failed: " . $e->getMessage());
            }
        });

        $this->info("✅ Staff reminder sent to {$totalSent} lecturers.");
    }
}