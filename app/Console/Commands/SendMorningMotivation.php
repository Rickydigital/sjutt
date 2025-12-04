<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendMorningMotivation extends Command
{
    protected $signature = 'motivation:morning';
    protected $description = 'Send beautiful morning greeting + Bible & Quran verse to all active students';

    // Beautiful Bible & Quran verses (feel free to expand)
    private $bibleVerses = [
        "Philippians 4:13 → I can do all things through Christ who strengthens me.",
        "Joshua 1:9 → Be strong and courageous. Do not be afraid; do not be discouraged, for the Lord your God will be with you wherever you go.",
        "Psalm 46:1 → God is our refuge and strength, an ever-present help in trouble.",
        "Isaiah 40:31 → Those who hope in the Lord will renew their strength. They will soar on wings like eagles.",
        "Proverbs 3:5-6 → Trust in the Lord with all your heart and lean not on your own understanding.",
        "Matthew 19:26 → With man this is impossible, but with God all things are possible.",
        "Psalm 118:24 → This is the day that the Lord has made; let us rejoice and be glad in it.",
    ];

    private $quranVerses = [
        "Surah Ad-Duha 93:4-5 → And the Hereafter is better for you than the first [life]. And your Lord is going to give you, and you will be satisfied.",
        "Surah Ash-Sharh 94:5-6 → For indeed, with hardship [will be] ease. Indeed, with hardship [will be] ease.",
        "Surah Al-Baqarah 2:286 → Allah does not burden a soul beyond that it can bear.",
        "Surah Ar-Ra'd 13:11 → Indeed, Allah will not change the condition of a people until they change what is in themselves.",
        "Surah Az-Zumar 39:53 → Do not despair of the mercy of Allah. Indeed, Allah forgives all sins.",
        "Surah Al-Inshirah 94:7 → So when you have finished [your duties], then stand up [for worship].",
        "Surah Al-Ankabut 29:69 → And those who strive for Us — We will surely guide them to Our ways.",
    ];

    private $motivationalQuotes = [
        "Today is a blank page — write a beautiful story!",
        "You are stronger than yesterday. Now go and conquer today!",
        "Small steps today → big success tomorrow.",
        "Wake up with determination. Go to bed with satisfaction.",
        "Your future is created by what you do today, not tomorrow.",
        "Believe you can and you're halfway there.",
        "Every morning is a new chance to be better than yesterday.",
    ];

    public function handle()
    {
        $today = Carbon::now()->format('l'); // Monday, Tuesday...
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
            'Monday'    => "Happy Monday! 🌅 Let's start the week strong!",
            'Friday'    => "Alhamdulillah it's Friday! 🕌 May your Jumu'ah be blessed!",
            'Sunday'    => "Blessed Sunday! 🙏 Rest, reflect, and recharge.",
            default     => "Good Morning! ☀️ Have a wonderful {$dayNameSwahili}!",
        };

        $bible = $this->bibleVerses[array_rand($this->bibleVerses)];
        $quran = $this->quranVerses[array_rand($this->quranVerses)];
        $motivation = $this->motivationalQuotes[array_rand($this->motivationalQuotes)];

        $title = "Good Morning, Warrior! 🌟";
        $body = "{$greeting}\n\n" .
                "📖 {$bible}\n\n" .
                "🕌 {$quran}\n\n" .
                "💪 {$motivation}\n\n" .
                "You're destined for greatness today! Go shine! ✨";

        $students = Student::where('status', 'active')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->select('first_name', 'fcm_token')
            ->get();

        if ($students->isEmpty()) {
            $this->info('No active students with FCM token found.');
            return 0;
        }

        $tokens = $students->pluck('fcm_token')->filter()->values()->chunk(500); // FCM limit: 500 per request

        $messaging = Firebase::messaging();
        $notification = Notification::create($title, $body);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withDefaultSounds()
            ->withData([
                'type' => 'morning_motivation',
                'day'  => $today,
            ]);

        $totalSuccess = 0;

        foreach ($tokens as $chunk) {
            try {
                $report = $messaging->sendMulticast($message, $chunk->toArray());
                $totalSuccess += $report->successes()->count();

                foreach ($report->invalidTokens() as $invalidToken) {
                    // Optional: Clean invalid tokens
                    // Student::where('fcm_token', $invalidToken)->update(['fcm_token' => null]);
                    Log::warning("Invalid FCM token: {$invalidToken}");
                }
            } catch (\Exception $e) {
                Log::error('Morning motivation FCM failed: ' . $e->getMessage());
            }
        }

        $this->info("Morning motivation sent to {$totalSuccess} students! 🌞");
        return 0;
    }
}