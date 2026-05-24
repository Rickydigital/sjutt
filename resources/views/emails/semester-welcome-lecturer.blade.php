<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome New Semester - SJUT</title>
    <style>
        body { margin:0; padding:0; background:#f8f9fb; font-family: 'Segoe UI', Arial, sans-serif; }
        .main-table { max-width: 600px; }
        @media only screen and (max-width: 600px) {
            .main-table { width: 95% !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f8f9fb;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding:40px 0;">
        <tr>
            <td align="center">
                <table class="main-table" width="600" border="0" cellpadding="0" cellspacing="0" 
                       style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 15px 40px rgba(0,0,0,0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #582B86, #7d4caf); padding:50px 20px;">
                            <img src="https://timetable.sjut.ac.tz/images/logo.png" 
                                 alt="SJUT Logo" 
                                 width="130" 
                                 style="display:block; border:0; background:#ffffff; padding:14px; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.15);">
                            <h1 style="color:#ffffff; margin:25px 0 0; font-size:28px; font-weight:700;">
                                St. John's University of Tanzania
                            </h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:50px 40px; text-align:center;">
                            <h2 style="color:#582B86; margin:0 0 25px; font-size:30px;">
                                Welcome Back, {{ $lecturer->name }}!
                            </h2>
                            
                            <p style="font-size:18px; color:#444; line-height:1.8; margin-bottom:30px;">
                                Thank you for your dedication and commitment to academic excellence.
                            </p>

                            <div style="background:#f8f9fa; padding:35px; border-radius:16px; margin:30px 0; text-align:left;">
                                <p style="font-size:17px; line-height:1.8; color:#333;">
                                    We warmly welcome you to the new academic semester.<br><br>
                                    Your mentorship, guidance, and hard work continue to shape the future of our students.<br><br>
                                    We wish you a productive, peaceful, and highly successful semester.
                                </p>
                            </div>

                            <p style="color:#582B86; font-size:18px; font-weight:600;">
                                Thank you for being an inspiration!
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#582B86; color:#ffffff; padding:35px 20px; text-align:center; font-size:14px;">
                            <strong>© {{ date('Y') }} St. John's University of Tanzania</strong><br>
                            <span style="opacity:0.9;">Empowering Minds • Transforming Lives</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>