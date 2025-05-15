<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class OtpController extends Controller
{
    public function requestOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['error' => 'Email not registered'], 404);
        }

        // Generate 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(5);

        // Store OTP
        Otp::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'used' => false,
        ]);

        // Send OTP via email
        try {
            Mail::to($email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send OTP'], 500);
        }

        return response()->json(['message' => 'OTP sent successfully'], 200);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $otpRecord = Otp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return response()->json(['error' => 'Invalid or expired OTP'], 400);
        }

        $otpRecord->update(['used' => true]);

        return response()->json(['message' => 'OTP verified successfully'], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'Email not registered'], 404);
        }

        // Verify recent OTP usage
        $otpRecord = Otp::where('email', $request->email)
            ->where('used', true)
            ->where('updated_at', '>', Carbon::now()->subMinutes(10))
            ->first();

        if (!$otpRecord) {
            return response()->json(['error' => 'No valid OTP session'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Invalidate all OTPs for this email
        Otp::where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully'], 200);
    }
}