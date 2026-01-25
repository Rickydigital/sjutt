<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendStaffMorningMotivation extends Command
{
    protected $signature = 'motivation:staff-morning';
    protected $description = 'Send daily morning spiritual + motivational message to all lecturers and staff';

    // Expanded with more beautiful, relevant verses
    private $bibleVerses = [
        "Philippians 4:13 → I can do all things through Christ who strengthens me.",
        "Colossians 3:23 → Whatever you do, work at it with all your heart, as working for the Lord.",
        "Joshua 1:9 → Be strong and courageous. Do not be afraid; for the Lord your God will be with you wherever you go.",
        "Proverbs 22:29 → Do you see someone skilled in their work? They will serve before kings.",
        "2 Timothy 2:15 → Do your best to present yourself to God as one approved, a worker who does not need to be ashamed.",
        "Psalm 90:17 → May the favor of the Lord our God rest on us; establish the work of our hands.",
        "Isaiah 41:10 → Fear not, for I am with you; be not dismayed, for I am your God.",
        "Galatians 6:9 → Let us not become weary in doing good, for at the proper time we will reap a harvest if we do not give up.",
        "Psalm 118:24 → This is the day the Lord has made; let us rejoice and be glad in it.",
        "Ephesians 2:10 → We are God’s handiwork, created in Christ Jesus to do good works.",
    ];

    private $quranVerses = [
        "Surah Ash-Sharh 94:5-6 → Verily, with every hardship comes ease.",
        "Surah Al-Baqarah 2:286 → Allah does not burden a soul beyond that it can bear.",
        "Surah Az-Zumar 39:53 → Say: 'O My servants who have transgressed against themselves, do not despair of the mercy of Allah.'",
        "Surah Ar-Ra'd 13:11 → Indeed, Allah will not change the condition of a people until they change what is in themselves.",
        "Surah Al-Ankabut 29:69 → And those who strive for Us — We will surely guide them to Our ways.",
        "Surah Al-Isra 17:80 → And say: 'My Lord! Cause me to enter a sound entrance and to exit a sound exit, and grant me from Yourself a supporting authority.'",
        "Surah Ta-Ha 20:132 → Enjoin prayer on your family and be constant therein. We ask you not for provision; We provide for you, and the [best] outcome is for [those of] righteousness.",
        "Surah Ad-Duha 93:11 → And as for the favor of your Lord, report [it].",
        "Surah Al-Inshirah 94:7 → So when you have finished [your duties], then stand up [for worship].",
        "Surah Al-Mu'minun 23:1-2 → Certainly will the believers have succeeded: They who are during their prayer humbly submissive.",
    ];

    private $staffMotivationalQuotes = [
        "Great teachers don't just teach subjects — they inspire minds and shape futures.",
        "Your influence as an educator goes beyond the classroom. You're building tomorrow today.",
        "Patience, passion, and perseverance — the three pillars of exceptional teaching.",
        "Every lecture you deliver plants a seed. Some will grow into forests.",
        "Leadership in education is not about being in charge — it's about taking care of those in your charge.",
        "The best lecturers don't fill buckets — they light fires.",
        "Excellence is never an accident. It is the result of sincere effort and intelligent direction.",
        "You are not just teaching courses. You are mentoring dreams.",
        "A good teacher explains. A great teacher inspires. You are great.",
        "Your dedication today becomes a student's success story tomorrow.",
        "In the hearts of your students, you will live forever through the knowledge you share.",
        "Teaching is the profession that creates all other professions.",
    ];

    public function handle()
    {
        $credentialsPath = storage_path('app/firebase-credentials.json');

        if (!file_exists($credentialsPath)) {
            $this->error('Firebase credentials file not found.');
            Log::error('Firebase credentials missing for staff morning motivation');
            return 1;
        }

        try {
            $messaging = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->createMessaging();
        } catch (\Exception $e) {
            $this->error('Failed to initialize Firebase: ' . $e->getMessage());
            Log::error('Staff motivation Firebase init failed: ' . $e->getMessage());
            return 1;
        }

        $today = Carbon::now('Africa/Dar_es_Salaam')->format('l');
        $dayNameSwahili = [
            'Monday'    => 'Jumatatu',
            'Tuesday'   => 'Jumanne',
            'Wednesday' => 'Jumatano',
            'Thursday'  => 'Alhamisi',
            'Friday'    => 'Ijumaa',
            'Saturday'  => 'Jumamosi',
            'Sunday'    => 'Jumapili',
        ][$today] ?? $today;

        $greeting = match ($today) {
            'Monday'    => "Happy Monday, dear colleague! Let's inspire a new week of learning!",
            'Friday'    => "Alhamdulillah for Friday! May your Jumu'ah be filled with peace and blessings.",
            'Sunday'    => "Blessed Sunday! A perfect day to reflect and prepare for another meaningful week.",
            default     => "Good Morning, esteemed colleague! Happy {$dayNameSwahili}!",
        };

        $bible = $this->bibleVerses[array_rand($this->bibleVerses)];
        $quran = $this->quranVerses[array_rand($this->quranVerses)];
        $motivation = $this->staffMotivationalQuotes[array_rand($this->staffMotivationalQuotes)];

        $title = "Good Morning, Inspirer of Minds! 🌟";
        $body  = "Assalamu Alaikum & Blessed Morning!\n\n" .
                 "{$greeting}\n\n" .
                 "📖 Bible Inspiration:\n{$bible}\n\n" .
                 "🕌 Quran Reflection:\n{$quran}\n\n" .
                 "💡 Thought for Today:\n{$motivation}\n\n" .
                 "Thank you for your dedication to shaping young minds. Your work matters deeply.\n" .
                 "Have a purposeful and blessed day!";

        // Get all active staff with roles: Lecturer, Admin, HOD, Dean, etc.
        $staff = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Lecturer', 'Administrator', 'HOD', 'Dean', 'Staff']);
            })
            ->where('status', 'active')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->select('id', 'name', 'fcm_token')
            ->get();

        if ($staff->isEmpty()) {
            $this->info('No active staff members with FCM tokens found.');
            Log::info('Staff morning motivation: No recipients found.');
            return 0;
        }

        $tokens = $staff->pluck('fcm_token')->filter()->chunk(500);

        $notification = Notification::create($title, $body);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withDefaultSounds()
            ->withData([
                'type' => 'staff_morning_motivation',
                'day'  => $today,
            ]);

        $totalSuccess = 0;
        $clearedTokens = 0;

        foreach ($tokens as $chunk) {
            try {
                $report = $messaging->sendMulticast($message, $chunk->toArray());

                $totalSuccess += $report->successes()->count();

                // Clean invalid tokens
                foreach ($report->invalidTokens() as $index => $invalidToken) {
                    $user = $staff->skip($index)->first(); // Approximate match
                    if ($user) {
                        $user->update(['fcm_token' => null]);
                        $clearedTokens++;
                        Log::info("Cleared invalid FCM token for staff: {$user->name} ({$user->id})");
                    }
                }
            } catch (\Exception $e) {
                Log::error('Staff motivation batch send failed: ' . $e->getMessage());
            }
        }

        $this->info("Staff morning motivation sent to {$totalSuccess} staff members!");
        if ($clearedTokens > 0) {
            $this->warn("Cleared {$clearedTokens} invalid tokens.");
        }

        Log::info("Staff morning motivation sent to {$totalSuccess} recipients.");

        return 0;
    }
}