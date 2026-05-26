<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendNightEncouragementNotifications extends Command
{
    protected $signature = 'notifications:night';
    protected $description = 'Send funny and encouraging night notifications to students and staff';

    public function handle(): void
    {
        $this->info('🌙 Starting night encouragement notifications...');

        $credentialsPath = storage_path('app/firebase-credentials.json');

        if (!file_exists($credentialsPath)) {
            $this->error('Firebase credentials file not found.');
            Log::error('Night notifications failed: Firebase credentials missing.');
            return;
        }

        try {
            $messaging = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->createMessaging();
        } catch (\Exception $e) {
            $this->error('Firebase initialization failed: ' . $e->getMessage());
            Log::error('Firebase init failed: ' . $e->getMessage());
            return;
        }

        $this->sendStudentNotifications($messaging);
        $this->sendStaffNotifications($messaging);

        $this->info('✅ Night notifications completed successfully.');
    }

    private function sendStudentNotifications($messaging): void
    {
        $students = Student::where('status', 'Active')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->select('id', 'first_name', 'fcm_token')
            ->get();

        $this->info("📚 Found {$students->count()} active students.");

        $studentMessages = [
            "😂 Sleep early champion... tomorrow’s attendance is waiting for you 📚🙏",

            "🌙 Good night future billionaire 😄 Don’t forget tomorrow’s session please!",

            "😅 Your bed is temporary but your GPA is forever. Prepare for tomorrow 😂📖",

            "🙏 May God protect and guide you tonight. See you in class tomorrow 🌟",

            "😂 Please charge your phone, brain, and motivation before tomorrow morning 🔋📚",

            "🌙 Don’t worry too much... one step daily and success will come 💪",

            "😄 Reminder: lecturers wake up early too ooo 😂 Please attend tomorrow’s sessions.",

            "🙏 Sleep peacefully and remember: small effort every day changes the future 📖",

            "😂 Tomorrow is not a public holiday my friend... set that alarm now ⏰",

            "🌟 May God bless your studies and give you success this semester 🙏",
        ];

        $totalSent = 0;
        $clearedTokens = 0;

        $students->chunk(300)->each(function ($chunk) use (
            $messaging,
            &$totalSent,
            &$clearedTokens,
            $studentMessages
        ) {
            $messages = [];

            foreach ($chunk as $student) {

                $randomMessage = $studentMessages[array_rand($studentMessages)];

                $notification = Notification::create(
                    '🌙 SJUT Night Reminder',
                    "Dear {$student->first_name}, {$randomMessage}"
                );

                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withDefaultSounds()
                    ->withData([
                        'type' => 'night_encouragement',
                    ])
                    ->toToken($student->fcm_token);

                $messages[] = $message;
            }

            try {

                $report = $messaging->sendAll($messages);

                $totalSent += $report->successes()->count();

                foreach ($report->invalidTokens() as $invalidToken) {

                    $student = $chunk->firstWhere('fcm_token', $invalidToken);

                    if ($student) {
                        $student->update([
                            'fcm_token' => null,
                        ]);

                        $clearedTokens++;
                    }
                }

            } catch (\Exception $e) {

                Log::error('Student night notification failed: ' . $e->getMessage());
            }

            sleep(1);
        });

        $this->info("✅ Student notifications sent: {$totalSent}");

        if ($clearedTokens > 0) {
            $this->warn("⚠️ Cleared invalid student tokens: {$clearedTokens}");
        }
    }

    private function sendStaffNotifications($messaging): void
    {
        $staff = User::where('status', 'active')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->select('id', 'name', 'fcm_token')
            ->get();

        if ($staff->isEmpty()) {
            $this->warn('No staff devices found.');
            return;
        }

        $this->info("👨‍🏫 Found {$staff->count()} staff members.");

        $staffMessages = [
            "🙏 Thank you for supporting students every day. Have a peaceful night.",

            "🌙 May God bless your work and efforts this semester. Rest well.",

            "📚 Thank you for guiding tomorrow’s leaders. Wishing you a peaceful evening 🙏",

            "🙏 Your dedication to students is highly appreciated. Good night and stay blessed.",
        ];

        $totalSent = 0;

        $staff->chunk(300)->each(function ($chunk) use (
            $messaging,
            &$totalSent,
            $staffMessages
        ) {

            $messages = [];

            foreach ($chunk as $member) {

                $randomMessage = $staffMessages[array_rand($staffMessages)];

                $notification = Notification::create(
                    '🌙 Staff Night Reminder',
                    "Dear {$member->name}, {$randomMessage}"
                );

                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withDefaultSounds()
                    ->withData([
                        'type' => 'staff_night_encouragement',
                    ])
                    ->toToken($member->fcm_token);

                $messages[] = $message;
            }

            try {

                $report = $messaging->sendAll($messages);

                $totalSent += $report->successes()->count();

            } catch (\Exception $e) {

                Log::error('Staff night notification failed: ' . $e->getMessage());
            }

            sleep(1);
        });

        $this->info("✅ Staff notifications sent: {$totalSent}");
    }
}