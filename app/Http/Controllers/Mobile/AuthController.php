<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionVote;
use App\Models\Otp;
use App\Models\Program;
use App\Models\Student;
use App\Notifications\OtpNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'reg_no'    => 'required|string',
            'password'  => 'required|string',
            'fcm_token' => 'sometimes|string',
        ]);

        $student = Student::where('reg_no', $request->reg_no)->first();

        if (!$student || !Hash::check($request->password, $student->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Election Vote Lock - Block Login Within 12 Hours After Voting
        |--------------------------------------------------------------------------
        */
        $activeElection = Election::where('status', 'open')
            ->where('is_active', true)
            ->first();

        if ($activeElection) {
            $recentVote = ElectionVote::where('election_id', $activeElection->id)
                ->where('student_id', $student->id)
                ->where('created_at', '>=', now()->subHours(12))
                ->exists();

            if ($recentVote) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'You have already completed voting. Login is disabled for 12 hours after voting.'
                ], 403);
            }
        }

        if ($request->filled('fcm_token')) {
            $student->update(['fcm_token' => $request->fcm_token]);
        }

        $token = $student->createToken('mobile-app')->plainTextToken;

        $isComplete = $student->status === 'Active'
            && $student->program_id
            && $student->faculty_id
            && $student->phone;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login successful',
            'data'    => [
                'token'            => $token,
                'student'          => $student->load('faculty', 'program'),
                'profile_complete' => $isComplete,
                'requires_update'  => !$isComplete,
            ]
        ]);
    }

    public function getPrograms()
    {
        $programs = Program::with('faculties')->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Programs retrieved successfully',
            'data'    => ['programs' => $programs]
        ], 200);
    }

    public function register(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'email'      => 'required|email',
            'phone'      => 'required|string|max:20',
            'gender'     => 'required|in:male,female',
            'password'   => 'required|string|min:6|confirmed',
            'faculty_id' => 'required|exists:faculties,id',
            'program_id' => 'required|exists:programs,id',
            'status'     => 'required|in:Active,Alumni',
        ]);

        $student = Student::findOrFail($request->student_id);

        if ($student->status === 'Active' && $student->phone && $student->email) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Profile already completed'
            ], 400);
        }

        if (Student::where('email', $request->email)->where('id', '!=', $student->id)->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email already taken'
            ], 422);
        }

        $student->update([
            'email'      => $request->email,
            'phone'      => $request->phone,
            'gender'     => $request->gender,
            'password'   => bcrypt($request->password),
            'faculty_id' => $request->faculty_id,
            'program_id' => $request->program_id,
            'status'     => $request->status,
        ]);

        $token = $student->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'status'           => 'success',
            'message'          => 'Profile completed successfully',
            'token'            => $token,
            'student'          => $student->fresh()->load('faculty', 'program'),
            'profile_complete' => true,
            'user_type'        => $request->status
        ], 201);
    }

   public function requestRegistrationOtp(Request $request, \App\Services\NextSmsService $smsService)
{
    $request->validate([
        'student_id' => 'required|exists:students,id',
        'email'      => 'required|email',
        'phone'      => 'required|string|max:20',
        'gender'     => 'required|in:male,female',
        'password'   => 'required|string|min:6',
        'faculty_id' => 'required|exists:faculties,id',
        'program_id' => 'required|exists:programs,id',
        'status'     => 'required|in:Active,Alumni',
    ]);

    $student = Student::findOrFail($request->student_id);

    if ($student->status === 'Active' && $student->phone && $student->email) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Profile already completed'
        ], 400);
    }

    if (Student::where('email', $request->email)->where('id', '!=', $student->id)->exists()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Email already in use'
        ], 422);
    }

    $data = [
        'student_id' => $student->id,
        'email'      => $request->email,
        'phone'      => $request->phone,
        'gender'     => $request->gender,
        'password'   => bcrypt($request->password),
        'faculty_id' => $request->faculty_id,
        'program_id' => $request->program_id,
        'status'     => $request->status,
    ];

    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    Otp::updateOrCreate(
        ['email' => $request->email],
        [
            'otp'        => $otp,
            'data'       => json_encode($data),
            'expires_at' => now()->addMinutes(10),
            'used'       => false,
        ]
    );

    Notification::route('mail', $request->email)
        ->notify(new OtpNotification($otp, 'Verify Your Account'));

    $smsMessage = "Your SJUT verification code is {$otp}. It expires in 10 minutes.";

    Log::info('Sending registration OTP SMS', [
        'phone' => $request->phone,
    ]);

    $smsResult = $smsService->sendSms($request->phone, $smsMessage);

    Log::info('Registration OTP SMS response', [
        'phone'  => $request->phone,
        'result' => $smsResult,
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'OTP sent to your email and phone',
        'data'    => [
            'email'      => $request->email,
            'phone'      => $request->phone,
            'sms_sent'   => $smsResult['ok'] ?? false,
            'sms_status' => $smsResult['message'] ?? null,
        ]
    ]);
}

 public function resendRegistrationOtp(Request $request, \App\Services\NextSmsService $smsService)
{
    $request->validate([
        'email' => 'required|email',
    ]);

    $email = $request->email;

    $otpRecord = Otp::where('email', $email)
        ->where('used', false)
        ->first();

    if (!$otpRecord) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Invalid data, Please register again.'
        ], 500);
    }

    $data = json_decode($otpRecord->data, true);

    if (!$data || empty($data['phone'])) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Phone number not found. Please register again.'
        ], 422);
    }

    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    $otpRecord->update([
        'otp'        => $otp,
        'expires_at' => now()->addMinutes(5),
        'used'       => false,
    ]);

    Notification::route('mail', $email)
        ->notify(new OtpNotification($otp, 'Verify Your Account Resent Code'));

    $smsMessage = "Your SJUT verification code is {$otp}. It expires in 5 minutes.";

    Log::info('Sending OTP SMS', [
        'phone' => $data['phone'],
        'otp'   => $otp,
    ]);

    $smsResult = $smsService->sendSms($data['phone'], $smsMessage);

    Log::info('SMS Provider Response', [
        'phone'    => $data['phone'],
        'response' => $smsResult,
    ]);

    if ($smsResult['ok'] ?? false) {
        Log::info('OTP SMS sent successfully', [
            'phone'               => $data['phone'],
            'reference'           => $smsResult['reference'] ?? null,
            'provider_message_id' => $smsResult['provider_message_id'] ?? null,
        ]);
    } else {
        Log::error('OTP SMS failed', [
            'phone'    => $data['phone'],
            'error'    => $smsResult['message'] ?? 'Unknown error',
            'response' => $smsResult,
        ]);
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'OTP sent successfully by email and SMS',
        'data'    => [
            'email'      => $email,
            'phone'      => $data['phone'],
            'sms_sent'   => $smsResult['ok'] ?? false,
            'sms_status' => $smsResult['message'] ?? null,
        ]
    ], 200);
}
    public function verifyRegistrationOtp(Request $request)
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

        $student = Student::findOrFail($data['student_id']);

        $student->update([
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'gender'     => $data['gender'],
            'password'   => $data['password'],
            'faculty_id' => $data['faculty_id'],
            'program_id' => $data['program_id'],
            'status'     => $data['status'],
        ]);

        $otpRecord->update(['used' => true]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Account activated successfully!',
            'data'    => [
                'student'          => $student->load('faculty', 'program'),
                'profile_complete' => true,
                'user_type'        => $data['status']
            ]
        ], 201);
    }

    public function logout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:students,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $student = Student::find($request->id);

        if (!$student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Student not found'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Election Vote Lock - Block Logout Within 12 Hours After Voting
        |--------------------------------------------------------------------------
        */
        $activeElection = Election::where('status', 'open')
            ->where('is_active', true)
            ->first();

        if ($activeElection) {
            $recentVote = ElectionVote::where('election_id', $activeElection->id)
                ->where('student_id', $student->id)
                ->where('created_at', '>=', now()->subHours(12))
                ->exists();

            if ($recentVote) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Logout is disabled for 12 hours after completing election voting.'
                ], 403);
            }
        }

        $student->tokens()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Profile retrieved successfully',
            'data'    => $request->user()
        ], 200);
    }

    public function editProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'    => 'sometimes|exists:students,id',
            'faculty_id' => 'sometimes|exists:faculties,id',
            'program_id' => 'sometimes|exists:programs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $authenticatedUser = $request->user();
        $userIdToEdit = $request->input('user_id', $authenticatedUser->id);

        if ($userIdToEdit != $authenticatedUser->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $studentToEdit = Student::find($userIdToEdit);

        if (!$studentToEdit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $studentToEdit->update($request->only('faculty_id', 'program_id'));

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'data'    => $studentToEdit->load(['faculty', 'program'])
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'          => 'sometimes|exists:students,id',
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $authenticatedUser = $request->user();
        $userIdToChange = $request->input('user_id', $authenticatedUser->id);

        if ($userIdToChange != $authenticatedUser->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $studentToChange = Student::find($userIdToChange);

        if (!$studentToChange) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found'
            ], 404);
        }

        if (!Hash::check($request->current_password, $studentToChange->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Current password incorrect'
            ], 401);
        }

        $studentToChange->password = bcrypt($request->new_password);
        $studentToChange->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Password changed'
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $student = Student::where('email', $request->email)->first();

        if (!$student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email not found'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Use OTP flow to reset password'
        ], 200);
    }

    public function storeToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $student = Auth::guard('sanctum')->user();

        if (!$student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $student->update(['fcm_token' => $request->token]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Token stored successfully'
        ], 200);
    }

    public function updateOnlineStatus(Request $request)
    {
        $request->validate([
            'is_online' => 'required|boolean',
        ]);

        $student = $request->user();

        $student->update(['is_online' => $request->is_online]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Online status updated'
        ], 200);
    }

    public function requestOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->email;

        $student = Student::where('email', $email)->first();

        if (!$student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email not found'
            ], 404);
        }

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(5);

        Otp::create([
            'email'      => $email,
            'otp'        => $otp,
            'expires_at' => $expiresAt,
            'used'       => false,
        ]);

        try {
            $student->notify(new OtpNotification($otp, 'Your Requested OTP'));

            Log::info('Password reset OTP notification sent to: ' . $email);
        } catch (\Exception $e) {
            Log::error('Failed to send password reset OTP notification to ' . $email . ': ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'OTP sent successfully',
            'data'    => ['email' => $email]
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $otpRecord = Otp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        $otpRecord->update(['used' => true]);

        return response()->json([
            'status'  => 'success',
            'message' => 'OTP verified successfully'
        ], 200);
    }

    public function forgetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $student = Student::where('email', $request->email)->first();

        if ($student) {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            Otp::updateOrCreate(
                ['email' => $request->email],
                [
                    'otp'        => $otp,
                    'data'       => json_encode([
                        'purpose'    => 'reset_password',
                        'student_id' => $student->id,
                    ]),
                    'expires_at' => now()->addMinutes(10),
                    'used'       => false,
                ]
            );

            $student->notify(new OtpNotification($otp, 'Verify Your Account'));
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'If an account with that email exists, an OTP has been sent.'
        ]);
    }

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
                'message' => 'Invalid OTP or email.'
            ], 400);
        }

        $data = json_decode($otpRecord->data, true);

        if (!isset($data['purpose']) || $data['purpose'] !== 'reset_password') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid OTP purpose.'
            ], 400);
        }

        $student = Student::findOrFail($data['student_id']);

        $otpRecord->update(['used' => true]);

        $tempToken = $student->createToken('reset-password-temp', ['*'], now()->addMinutes(15))
            ->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'OTP verified successfully.',
            'data'    => [
                'token'   => $tempToken,
                'student' => [
                    'id'         => $student->id,
                    'first_name' => $student->first_name ?? $student->firstName ?? '',
                    'last_name'  => $student->last_name ?? $student->lastName ?? '',
                    'reg_no'     => $student->reg_no,
                    'email'      => $student->email,
                    'phone'      => $student->phone,
                    'gender'     => $student->gender,
                    'status'     => $student->status,
                    'program_id' => $student->program_id,
                    'faculty_id' => $student->faculty_id,
                ]
            ]
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $student = $request->user();

        if (!$student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized or token expired.'
            ], 401);
        }

        $student->password = bcrypt($request->password);
        $student->save();

        $student->tokens()->where('name', 'reset-password-temp')->delete();

        $newToken = $student->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Password reset successfully.',
            'data'    => [
                'token' => $newToken
            ]
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'user_id'   => 'required|exists:students,id',
            'fcm_token' => 'required|string',
        ]);

        $student = Student::find($request->user_id);

        if ($student) {
            $student->update(['fcm_token' => $request->fcm_token]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not found.'
        ], 404);
    }
}