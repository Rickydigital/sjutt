<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\TimetableSemester;
use App\Models\User;
use App\Notifications\AcademicYearAnnouncementNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class SendAcademicYearAnnouncements extends Command
{
    protected $signature = 'academic-year:announce
                            {academic_year : Academic year, for example 2026/2027}
                            {--semester= : Semester label; defaults to the active timetable semester}
                            {--audience=all : all, students, alumni, or staff}
                            {--send : Deliver email and push notifications; otherwise preview only}';

    protected $description = 'Email and push new academic year announcements to students, alumni, and staff';

    public function handle(): int
    {
        $academicYear = trim((string) $this->argument('academic_year'));
        $audience = strtolower(trim((string) $this->option('audience')));

        if (!preg_match('/^\d{4}\/\d{4}$/', $academicYear)) {
            $this->error('Academic year must use the format 2026/2027.');
            return self::FAILURE;
        }

        if (!in_array($audience, ['all', 'students', 'alumni', 'staff'], true)) {
            $this->error('Audience must be all, students, alumni, or staff.');
            return self::FAILURE;
        }

        $activeSetup = TimetableSemester::getCurrent();
        $semester = trim((string) ($this->option('semester') ?: $activeSetup?->semester?->name ?: 'new semester'));

        if ($activeSetup && $activeSetup->academic_year !== $academicYear) {
            $this->warn("The active timetable is {$activeSetup->academic_year}, but the announcement is for {$academicYear}.");
        }

        $groups = $this->recipientGroups($audience);

        $this->table(
            ['Audience', 'Recipients', 'With email', 'With push token'],
            collect($groups)->map(fn (array $group, string $name) => [
                ucfirst($name),
                (clone $group['query'])->count(),
                (clone $group['query'])->whereNotNull('email')->where('email', '!=', '')->count(),
                (clone $group['query'])->whereNotNull('fcm_token')->where('fcm_token', '!=', '')->count(),
            ])->values()->all()
        );

        if (!$this->option('send')) {
            $this->warn('Preview only. Add --send to deliver the emails and push notifications.');
            return self::SUCCESS;
        }

        $messaging = $this->firebaseMessaging();
        $emailSent = 0;
        $emailFailed = 0;
        $pushSent = 0;
        $pushFailed = 0;

        foreach ($groups as $groupName => $group) {
            $copy = $this->messageCopy($groupName, $academicYear, $semester);

            $group['query']->orderBy('id')->chunkById(200, function ($recipients) use (
                $groupName,
                $copy,
                $academicYear,
                $messaging,
                &$emailSent,
                &$emailFailed,
                &$pushSent,
                &$pushFailed
            ): void {
                foreach ($recipients as $recipient) {
                    $name = $groupName === 'staff'
                        ? ($recipient->name ?: 'Staff Member')
                        : (trim(($recipient->first_name ?? '') . ' ' . ($recipient->last_name ?? '')) ?: 'Member');

                    if ($recipient->email) {
                        try {
                            LaravelNotification::route('mail', $recipient->email)
                                ->notify(new AcademicYearAnnouncementNotification(
                                    $name,
                                    $copy['title'],
                                    $copy['body'],
                                    $academicYear
                                ));
                            $emailSent++;
                        } catch (\Throwable $e) {
                            $emailFailed++;
                            Log::error('Academic year announcement email failed.', [
                                'audience' => $groupName,
                                'recipient_id' => $recipient->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    if ($messaging && $recipient->fcm_token) {
                        try {
                            $message = CloudMessage::new()
                                ->withNotification(FirebaseNotification::create($copy['title'], $copy['body']))
                                ->withDefaultSounds()
                                ->withData([
                                    'type' => 'academic_year_announcement',
                                    'audience' => $groupName,
                                    'academic_year' => $academicYear,
                                ])
                                ->toToken($recipient->fcm_token);

                            $messaging->send($message);
                            $pushSent++;
                        } catch (\Throwable $e) {
                            $pushFailed++;
                            Log::error('Academic year announcement push failed.', [
                                'audience' => $groupName,
                                'recipient_id' => $recipient->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            });
        }

        $this->info("Delivery complete. Email sent: {$emailSent}; email failed: {$emailFailed}; push sent: {$pushSent}; push failed: {$pushFailed}.");

        return ($emailFailed + $pushFailed) > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function recipientGroups(string $audience): array
    {
        $groups = [
            'students' => [
                'query' => Student::query()->where('status', 'Active'),
            ],
            'alumni' => [
                'query' => Student::query()->where('status', 'Alumni'),
            ],
            'staff' => [
                'query' => User::query()->where('status', 'active'),
            ],
        ];

        return $audience === 'all' ? $groups : [$audience => $groups[$audience]];
    }

    private function messageCopy(string $audience, string $academicYear, string $semester): array
    {
        return match ($audience) {
            'students' => [
                'title' => "Welcome to {$semester} – {$academicYear}",
                'body' => "A new semester has started. Please be ready and check the SJUT App for your active timetable, examination timetable, and academic calendar. We wish you success in your studies.",
            ],
            'alumni' => [
                'title' => 'Congratulations and Welcome to the SJUT Alumni Community',
                'body' => "Congratulations! Your student status is now Alumni. We warmly welcome you to the SJUT Alumni community and wish you every success in the next chapter of your life.",
            ],
            'staff' => [
                'title' => "Best Wishes for the {$academicYear} Academic Calendar",
                'body' => "As the new academic calendar and {$semester} begin, we wish all SJUT staff a successful and fulfilling academic year. Please check the SJUT App for the active timetable and calendar.",
            ],
        };
    }

    private function firebaseMessaging(): mixed
    {
        $credentialsPath = storage_path('app/firebase-credentials.json');

        if (!is_file($credentialsPath)) {
            $this->warn('Firebase credentials were not found. Email will be sent, but push delivery will be skipped.');
            return null;
        }

        try {
            return (new Factory)
                ->withServiceAccount($credentialsPath)
                ->createMessaging();
        } catch (\Throwable $e) {
            $this->warn('Firebase could not be initialized. Email will be sent, but push delivery will be skipped.');
            Log::error('Academic year announcement Firebase initialization failed.', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
