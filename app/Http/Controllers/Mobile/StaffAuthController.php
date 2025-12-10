<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StaffAuthController extends Controller
{
    // STAFF LOGIN
   
public function login(Request $request)
{
    $request->validate([
        'email'      => 'required|email',
        'password'   => 'required|string',
        'fcm_token'  => 'sometimes|string',
    ]);

    $user = User::where('email', $request->email)
                ->where('status', 'active')
                ->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Invalid credentials or account not active',
        ], 401);
    }

    // Update FCM token
    if ($request->filled('fcm_token')) {
        $user->update(['fcm_token' => $request->fcm_token]);
    }

    // Determine if profile is complete
    $hasPhone = !empty($user->phone);
    $isProfileComplete = $hasPhone;

    $token = $user->createToken('mobile-app')->plainTextToken;

    return response()->json([
        'status' => 'success',
        'message' => 'Login successful',
        'data' => [
            'token'            => $token,
            'user'             => $user->load('roles'),
            'profile_complete' => $isProfileComplete,
            'requires_update'  => !$isProfileComplete,
            'missing_fields'   => $isProfileComplete ? [] : ['phone'],
        ]
    ]);
}

    // PROFILE
    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Profile retrieved successfully',
            'data' => $request->user()->load('roles')
        ]);
    }

    // 1. Request OTP to verify/add phone
public function requestPhoneVerificationOtp(Request $request)
{
    $request->validate(['phone' => 'required|string|max:20']);

    $user = $request->user();

    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    Otp::updateOrCreate(
        ['phone' => $request->phone],
        [
            'otp'        => $otp,
            'data'       => json_encode([
                'purpose'  => 'verify_staff_phone',
                'user_id'  => $user->id,
                'phone'    => $request->phone,
                'email'    => $user->email,
            ]),
            'expires_at' => now()->addMinutes(10),
            'used'       => false,
        ]
    );

    // ONLY SEND VIA EMAIL — NO LOGS ANYMORE
    Mail::to($user->email)->send(new OtpMail($otp, 'Verify Your Account'));

    return response()->json([
        'status'   => 'success',
        'message'  => 'Verification code sent to your email',
        'phone'    => $request->phone
    ]);
}

// 2. Verify Phone OTP & Complete Profile
public function verifyPhoneOtp(Request $request)
{
    $request->validate([
        'phone' => 'required|string',
        'otp'   => 'required|string|size:6',
    ]);

    $otpRecord = Otp::where('phone', $request->phone)
        ->where('otp', $request->otp)
        ->where('used', false)
        ->where('expires_at', '>', now())
        ->first();

    if (!$otpRecord) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Invalid or expired OTP'
        ], 400);
    }

    $data = json_decode($otpRecord->data, true);

    if ($data['purpose'] !== 'verify_staff_phone') {
        return response()->json(['status' => 'error', 'message' => 'Invalid OTP purpose'], 400);
    }

    $user = User::findOrFail($data['user_id']);

    // Save verified phone
    $user->update([
        'phone' => $data['phone'],
    ]);

    $otpRecord->update(['used' => true]);

    return response()->json([
        'status'           => 'success',
        'message'          => 'Phone verified successfully!',
        'profile_complete' => true,
        'user'             => $user->load('roles')
    ]);
}

// 3. Resend OTP (optional)
public function resendPhoneOtp(Request $request)
{
    $request->validate(['phone' => 'required|string']);

    $otpRecord = Otp::where('phone', $request->phone)
        ->where('used', false)
        ->first();

    if (!$otpRecord) {
        return response()->json(['status' => 'error', 'message' => 'No pending OTP'], 400);
    }

    $newOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpRecord->update([
        'otp'        => $newOtp,
        'expires_at' => now()->addMinutes(10),
    ]);

    Mail::to($request->user()->email)->send(new OtpMail($newOtp, 'Your Verification Code'));

    return response()->json([
        'status'  => 'success',
        'message' => 'Verification code resent to your email']);
    }



    // EDIT PROFILE (name, phone, gender)
    public function editProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'   => 'sometimes|string|max:255',
            'phone'  => 'sometimes|string|max:20',
            'gender' => 'sometimes|in:male,female',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $user->update($request->only('name', 'phone', 'gender'));

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => $user->load('roles')
        ]);
    }

    // CHANGE PASSWORD
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 401);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully'
        ]);
    }

    // STORE FCM TOKEN
    public function storeToken(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $user = $request->user();
        $user->update(['fcm_token' => $request->token]);

        return response()->json([
            'status' => 'success',
            'message' => 'FCM token stored successfully'
        ]);
    }

    // UPDATE ONLINE STATUS
    public function updateOnlineStatus(Request $request)
    {
        $request->validate(['is_online' => 'required|boolean']);

        $user = $request->user();
        $user->update(['is_online' => $request->is_online]);

        return response()->json([
            'status' => 'success',
            'message' => 'Online status updated'
        ]);
    }

    // LOGOUT (revoke all tokens)
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    // FORGOT PASSWORD - Request OTP
    public function forgetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->status === 'active') {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            Otp::updateOrCreate(
                ['email' => $request->email],
                [
                    'otp'        => $otp,
                    'data'       => json_encode([
                        'purpose'   => 'reset_password_staff',
                        'user_id'   => $user->id,
                    ]),
                    'expires_at' => now()->addMinutes(10),
                    'used'       => false,
                ]
            );

           Mail::to($request->email)->send(new OtpMail($otp, 'Reset Your SJUT Password'));
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'If an active staff account exists, an OTP has been sent.'
        ]);
    }

    // VERIFY OTP FOR PASSWORD RESET
    public function verifyResetPasswordOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp'   => 'required|string|size:6',
    ]);

    $otpRecord = Otp::where('email', $request->email)
        ->where('otp', $request->otp)
        ->where('used', false)
        ->where('expires_at', '>', now())
        ->first();

    if (!$otpRecord) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Invalid or expired OTP'
        ], 400);
    }

    $data = json_decode($otpRecord->data, true);

    if (!isset($data['purpose']) || $data['purpose'] !== 'reset_password_staff') {
        return response()->json([
            'status'  => 'error',
            'message' => 'Invalid OTP purpose'
        ], 400);
    }

    $user = User::findOrFail($data['user_id']);

    $otpRecord->update(['used' => true]);

    $tempToken = $user->createToken('reset-password-temp-staff', ['*'], now()->addMinutes(15))
        ->plainTextToken;

    return response()->json([
        'status'  => 'success',
        'message' => 'OTP verified',
        'data'    => [
            'token' => $tempToken,
            'user'  => $user->makeVisible(['email'])->load('roles')
        ]
    ]);
}

    // FINAL RESET PASSWORD
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized or token expired'
            ], 401);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        $user->tokens()->where('name', 'reset-password-temp-staff')->delete();

        $newToken = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successfully',
            'data' => ['token' => $newToken]
        ]);
    }
}