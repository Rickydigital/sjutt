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

    "😂 Eid Mubarak Tomorrow some of you will eat until walking becomes group work 🐐🍛 But please remember sessions still exist and attendance technology is waiting for your fingerprint 😭📚 Non-Muslim friends, if you are not invited please relax 😄 your celebration is also coming and revenge invitations will happen 😂",

    "🐐 Eid Mubarak 😂 Please remember: visiting 14 houses for pilau is cardio, not academic leave 😭 Sessions continue immediately after Eid ooo 😄📚 Non-Muslim brothers and sisters, your turn to disturb us with food is also loading 😂",

    "😂 Tomorrow lecturers will open attendance sheet and only see dust blowing across campus 🌪️📚 Please don’t let technology miss you too much 😭 Non-Muslims, if nobody invites you today just wait patiently… your ceremony season will also arrive 😂",

    "🍖 Eid Mubarak champion 😄 If your stomach starts speaking Arabic after pilau please drink water immediately 😂 But don’t forget tomorrow’s sessions still need you 💪📚 Non-Muslim students, your food revenge mission is pending too 😂",

    "😂 Dear student, tomorrow is Eid not World Eating Competition 😭 Please leave food for other humans too 🍛 and remember biometric attendance never forgets 😄 Non-Muslims, don’t worry… one day we shall also hunt your celebration food 😂",

    "🐐 We already know some of you have prepared 3 outfits and zero assignment progress 😂 Eid Mubarak! Please come back before attendance starts crying 😭📚 Non-Muslim friends, your invitation season is also under preparation 😂",

    "😂 Tomorrow some students will say 'I’m just visiting one friend' then disappear until Monday 😭 Please remember sessions are still active champion 📚 Non-Muslims, if nobody sends location today just wait for your turn 😂",

    "🍛 Eid Mubarak 😂 Please don’t forget SJUT after receiving unlimited biryani sponsorships tomorrow 😄 Attendance technology still believes in you 😂📚 Non-Muslim students, your future ceremony invitations are also protected 😂",

    "😂 Warning: too much pilau may cause unexpected sleep during afternoon prayers 😭🐐 But lectures and attendance are waiting immediately after celebrations 📚 Non-Muslims, don’t feel left out… your celebration food season is coming too 😂",

    "🐐 Dear, if you receive invitation messages tonight please forward one to me too 😂 And please report back to sessions alive 😭📚 Non-Muslims, next celebration we also expect full revenge invitations 😄😂",

    "😂 Tomorrow campus security may think university closed because all students became food influencers 🍖😄 Please remind attendance system you still exist 😂📚 Non-Muslim friends, your own food festival loading soon 😂",

    "🌙 Eid Mubarak 😂 May Allah bless your food, your family, and your weak semester budget 😭💸 But don’t forget academic life resumes immediately after Eid 📚 Non-Muslims, your invitation comeback is also coming strongly 😂",

    "😂 Some of you tomorrow: breakfast at aunt’s house, lunch at neighbor’s house, dinner at ex’s house 😭🐐 But attendance needs your fingerprint more than food needs your stomach 😂📚 Non-Muslims, your turn to overfeed us is pending 😂",

    "🍖 Tomorrow’s mission for many students: eat first, regret later 😂 Eid Mubarak champion! Please don’t regret missing sessions too 😭📚 Non-Muslim brothers and sisters, your celebration revenge chapter is still loading 😂",

    "😂 Dear student, please don’t use 'Eid recovery' as excuse for missing sessions next week 😭📚 Technology already knows your fingerprint 😂 Non-Muslims, please continue waiting patiently for your own invitation season 😄",

    "🐐 Eid Mubarak 😄 Tomorrow many belts will suffer silently after round 7 of pilau 😂 But attendance system still expects your arrival champion 📚 Non-Muslims, don’t worry… your food celebration counterattack is near 😂",

    "😂 Attendance tomorrow will be looking for students like missing goats on Eid morning 😭🐐 Please report yourself back to class after celebrations 😂📚 Non-Muslims, your future invitation revenge shall also be respected 😂",

    "🍛 Dear, after tomorrow’s food levels please remember stairs still exist 😭😂 And so do your lectures and attendance 😄📚 Non-Muslim friends, your celebration food plans are also remembered 😂",

    "😂 Some students tomorrow will greet relatives they have never seen since last Eid just for pilau 🍖😄 But please don’t disappear from sessions too 😭📚 Non-Muslims, your own ceremony season is coming and we expect serious invitations too 😂",

    "🌙 Eid Mubarak champion 🙏 Enjoy fully… but if you eat until phone fingerprint stops working, that’s your own problem 😂 Attendance still wants you back 😭📚 Non-Muslim students, don’t panic… your food celebration revenge is also loading 😂",
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
                    '🌙📚 Your GPA Said “Enough Nonsense, Go Sleep” 😂',
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

            "🌙 Eid Mubarak 🙏 Thank you for your continued dedication. Wishing you a peaceful and blessed Eid celebration. Also, if you receive too many food invitations, kindly consider forwarding one to administration 😄",

            "🙏 Eid Mubarak 🎉 May Allah bless your family, your work, and your efforts. Enjoy the celebrations, but please don’t forget we are all competing in invitation collection season 😄",

            "🌙 Wishing you a blessed Eid Mubarak 🙏 Enjoy the festive moments with family and friends. If invitations increase beyond control, just remember campus will still be here after the celebrations 😄",

            "📚 Eid Mubarak 🙏 Thank you for your commitment and guidance. May this Eid bring you peace and happiness. If you get invited to too many houses, we officially support selective attendance of food events 😄",

            "🙏 Eid Mubarak 🎉 May this special day bring joy and renewal. Enjoy responsibly and remember, even academic life resumes after the feast season of invitations 😄",
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
                    'University Communication',
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