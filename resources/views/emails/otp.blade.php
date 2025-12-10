<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - SJUT</title>
    <style>
        body { margin:0; padding:0; background:#f8f9fb; font-family: 'Segoe UI', Arial, sans-serif; }
        a { text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .main-table { width: 95% !important; }
            .otp-digit { font-size: 36px !important; padding: 12px 8px !important; min-width: 50px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f8f9fb;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding:40px 0;">
        <tr>
            <td align="center">
                <!-- Main Email Container -->
                <table class="main-table" width="600" border="0" cellpadding="0" cellspacing="0" 
                       style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 15px 40px rgba(0,0,0,0.08); max-width:600px;">
                    
                    <!-- Header with Logo -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #582B86, #7d4caf); padding:50px 20px;">
                            <img src="https://timetable.sjut.ac.tz/images/logo.png" 
                                 alt="SJUT Logo" 
                                 width="130" 
                                 style="display:block; border:0; background:#ffffff; padding:14px; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.15);">
                            <h1 style="color:#ffffff; margin:25px 0 0; font-size:28px; font-weight:700; letter-spacing:0.5px;">
                                St. John's University of Tanzania
                            </h1>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding:50px 40px; text-align:center;">
                            <h2 style="color:#582B86; margin:0 0 20px; font-size:30px; font-weight:700;">
                                {{ $title }}
                            </h2>
                            <p style="font-size:17px; color:#555; line-height:1.7; margin:0 auto 40px; max-width:520px;">
                                {{ $bodyMessage }}
                            </p>

                            <!-- Horizontal OTP Display -->
                            <div style="margin:40px 0; padding:30px 20px; background:#f8f9fa; border-radius:16px; border:2px solid #e9ecef; display:inline-block;">
                                <table border="0" cellpadding="0" cellspacing="10" style="margin:0 auto;">
                                    <tr>
                                        @foreach(str_split($otp) as $digit)
                                            <td align="center" style="background:#582B86; color:#ffffff; border-radius:12px; min-width:62px;">
                                                <span class="otp-digit" style="display:block; font-size:44px; font-weight:800; padding:18px 10px; letter-spacing:0;">
                                                    {{ $digit }}
                                                </span>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </div>

                            <!-- Expiry & Security Note -->
                            <p style="color:#666; font-size:16px; line-height:1.8; margin:40px 0 20px;">
                                This code expires in <strong style="color:#582B86;">10 minutes</strong>.<br>
                                <span style="color:#e74c3c; font-weight:600;">Never share this code with anyone</span> — even if they say they're from SJUT.
                            </p>

                            <hr style="border:none; border-top:2px solid #eee; margin:45px 0;">

                            <p style="color:#888; font-size:14px; line-height:1.7;">
                                Didn't request this code? Simply ignore this email or contact us at<br>
                                <a href="mailto:it@sjut.ac.tz" style="color:#582B86; font-weight:600;">it@sjut.ac.tz</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#582B86; color:#ffffff; padding:30px 20px; text-align:center; font-size:14px;">
                            <strong>© {{ date('Y') }} St. John's University of Tanzania</strong><br><br>
                            <span style="opacity:0.9;">Made with ❤️ by SJUT Mobile App Team</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>