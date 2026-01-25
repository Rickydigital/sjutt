<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Illuminate\Support\HtmlString;

class SendAppDownloadEmail extends Command
{
    protected $signature = 'email:app-download';
    protected $description = 'Send email to all staff and lecturers inviting them to download the official SJUT Mobile App';

    // Exact roles from your RoleSeeder
    private $staffRoles = [
        'Admin',
        'Administrator',
        'Dean Of Students',
        'Director',
        'Timetable Officer',
        'Lecturer'
    ];

   public function handle()
{
    $staff = User::whereHas('roles', function ($query) {
        $query->whereIn('name', $this->staffRoles);
    })
    ->whereNotNull('email')
    ->where('email', '!=', '')
    ->where('status', 'active')
    ->get();

    if ($staff->isEmpty()) {
        $this->info('No active staff/lecturers found with valid email addresses.');
        return 0;
    }

    $this->info("Sending app download invitation to {$staff->count()} staff members...");

    $downloadLink = 'https://timetable.sjut.ac.tz/download-app/sjut-app-1.1.0.apk';

    $sent = 0;
    $failed = 0;

    foreach ($staff as $user) {
        $greeting = $this->getGreeting($user->gender ?? 'unknown', $user->name);

        try {
            Mail::html(
                $this->getEmailContent($greeting, $user->email, $downloadLink)->toHtml(),
                function (Message $message) use ($user) {
                    $message->to($user->email)
                            ->subject('📱 Official SJUT Mobile App – Now Available for Download')
                            ->from(config('mail.from.address'), 'St. John\'s University of Tanzania');
                }
            );

            $sent++;
            $this->line("✅ Sent → {$user->name} <{$user->email}>");

        } catch (\Exception $e) {
            $failed++;
            $this->error("❌ Failed → {$user->name} ({$user->email}): " . $e->getMessage());
        }
    }

    $this->newLine();
    $this->info("Campaign completed!");
    $this->info("Successfully sent: {$sent}");
    if ($failed > 0) {
        $this->warn("Failed: {$failed}");
    }

    return 0;
}

    private function getGreeting($gender, $name)
    {
        if (strtolower($gender) === 'male') {
            return "Dear Mr. {$name}";
        } elseif (strtolower($gender) === 'female') {
            return "Dear Mrs./Ms. {$name}";
        } else {
            return "Dear {$name}";
        }
    }

    private function getEmailContent($greeting, $email, $downloadLink)
    {
        return new HtmlString("
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.7; color: #333; background: #f9f9f9; }
                    .container { max-width: 650px; margin: 20px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                    h2 { color: #003366; }
                    .button {
                        display: inline-block;
                        background-color: #003366;
                        color: white !important;
                        padding: 16px 32px;
                        text-decoration: none;
                        border-radius: 8px;
                        font-weight: bold;
                        font-size: 16px;
                        margin: 20px 0;
                    }
                    .contacts { background: #f0f7ff; padding: 20px; border-radius: 10px; margin-top: 30px; border-left: 5px solid #003366; }
                    ul { padding-left: 20px; }
                    li { margin-bottom: 8px; }
                    code { background: #eee; padding: 2px 6px; border-radius: 4px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>{$greeting},</h2>

                    <p>We are delighted to inform you that the <strong>Official Mobile Application</strong> of <strong>St. John's University of Tanzania (SJUT)</strong> has been launched!</p>

                    <p>This app was developed in-house by our dedicated CICT team to support you in your daily academic responsibilities and keep you connected with the university community.</p>

                    <h3>📲 Download the SJUT App (Android Version)</h3>
                    <p>
                        <a href='{$downloadLink}' class='button'>Download SJUT App v1.1.0</a>
                    </p>

                    <p><strong>Safety Note:</strong> This APK is officially developed and digitally signed by SJUT CICT. It is completely safe to install. The app will soon be available on Google Play Store and Apple App Store.</p>

                    <h3>🔐 Login Instructions</h3>
                    <p>Please use the following credentials to log in:</p>
                    <ul>
                        <li><strong>Email:</strong> {$email}</li>
                        <li><strong>Password:</strong> <code>12345678</code></li>
                    </ul>
                    <p><strong>It is strongly advisable to change your password after logging in for better security.</strong></p>

                    <h3>🌟 Why You Should Use the SJUT App</h3>
                    <p>The app brings powerful tools directly to your phone:</p>
                    <ul>
                        <li>Receive <strong>notifications 30 minutes before</strong> your teaching sessions</li>
                        <li>View your <strong>personal weekly timetable</strong> clearly</li>
                        <li>See all <strong>courses assigned</strong> to you and registered students</li>
                        <li>Check <strong>venue details</strong> and find <strong>available/empty venues</strong></li>
                        <li>Stay updated with <strong>University News, Events, and Announcements</strong></li>
                        <li>Access everything offline once loaded</li>
                    </ul>

                    <p><em>Currently available for Android users only. The iOS version is in development and coming soon!</em></p>

                    <div class='contacts'>
                        <h3>📞 For Support or Inquiries, Please Contact:</h3>
                        <ul>
                            <li><strong>Shuubi Alphonce</strong> – Director of CICT<br>+255 757 872 790</li>
                            <li><strong>JohnKennedy Kungura</strong> – CICT Officer<br>+255 759 835 713</li>
                            <li><strong>Reuben Minaeli</strong><br>+255 714 698 583</li>
                            <li><strong>Emmanuel Matowo</strong><br>+255 716 536 995</li>
                        </ul>
                    </div>

                    <p>Thank you for your commitment and dedication to academic excellence at SJUT.</p>

                    <p>Warm regards,<br>
                    <strong>CICT Department</strong><br>
                    St. John's University of Tanzania</p>
                </div>
            </body>
            </html>
        ");
    }
}