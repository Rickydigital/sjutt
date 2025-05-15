<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SJUT OTP</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #582B86;">St. John’s University of Tanzania</h2>
        <p>Dear User,</p>
        <p>You requested to reset your password. Use the following OTP to proceed:</p>
        <h3 style="color: #582B86; font-size: 24px; text-align: center;">{{ $otp }}</h3>
        <p>This OTP is valid for 5 minutes. Do not share it with anyone.</p>
        <p>If you did not request this, please ignore this email or contact support at it@sjut.ac.tz.</p>
        <p>Best regards,<br>SJUT App Team</p>
    </div>
</body>
</html>