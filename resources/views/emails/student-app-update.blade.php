<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
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
        .whats-new { background: #e6f7ff; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #003366; }
        .contacts { background: #f0f7ff; padding: 20px; border-radius: 10px; margin-top: 30px; border-left: 5px solid #003366; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 14px; }
        ul { padding-left: 20px; }
        li { margin-bottom: 8px; }
        code { background: #eee; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>{{ $greeting }}</h2>

        <p>Great news! The <strong>Official SJUT Mobile App</strong> has been updated to <strong>version 1.1.0</strong> with exciting new features!</p>

        <p>If you were using the previous version, we kindly ask you to <strong>download and install the latest version</strong> to enjoy improved performance and new functionalities.</p>

        <h3>Download Latest Version (Android)</h3>
        <p>
            <a href="{{ $downloadLink }}" class="button">Download SJUT App v1.1.0</a>
        </p>

        <p><strong>Safety Note:</strong> This APK is officially developed and signed by SJUT CICT. It is 100% safe. The app will soon be on Google Play Store and Apple App Store.</p>

        <div class="whats-new">
            <h3>What's New in v1.1.0</h3>
            <ul>
                <li><strong>Group-Specific Notifications</strong> – Now receive reminders only for your registered group sessions</li>
                <li><strong>Auto-Update Timetable</strong> – Configure automatic timetable refresh so you always have the latest schedule</li>
                <li>Bug fixes and performance improvements</li>
                <li>Better offline support</li>
            </ul>
        </div>

        <h3>Login Instructions</h3>
        <p>Use your university credentials:</p>
        <ul>
            <li><strong>Email:</strong> {{ $student->email }}</li>
            <li><strong>Password:</strong> <code>12345678</code></li>
        </ul>
        <p><strong>It is strongly advisable to change your password after logging in for security.</strong></p>

        <h3>Key Features You Already Love</h3>
        <ul>
            <li>30-minute class reminders</li>
            <li>Personal timetable view</li>
            <li>Venue locations & empty venue finder</li>
            <li>University news, events & announcements</li>
            <li>Offline access</li>
        </ul>

        <p><em>iOS version coming soon!</em></p>

        <div class="contacts">
            <h3>For Support or Inquiries, Contact:</h3>
            <ul>
                <li><strong>Shuubi Alphonce</strong> – Director of CICT<br>+255 757 872 790</li>
                <li><strong>JohnKennedy Kungura</strong> – CICT Officer<br>+255 759 835 713</li>
                <li><strong>Reuben Minaeli</strong><br>+255 714 698 583</li>
                <li><strong>Emmanuel Matowo</strong><br>+255 716 536 995</li>
            </ul>
        </div>

        <p>Thank you for being part of the SJUT digital community!</p>

        <p>Warm regards,<br>
        <strong>CICT Department</strong><br>
        St. John's University of Tanzania</p>

        <div class="footer">
            <p><em>Empowered by TechNest SJUT Team</em></p>
        </div>
    </div>
</body>
</html>