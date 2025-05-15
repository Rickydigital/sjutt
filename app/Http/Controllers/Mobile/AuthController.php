<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\Student;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'reg_no' => 'required|string|unique:students,reg_no',
            'year_of_study' => 'required|integer|between:1,4',
            'faculty_id' => 'required|exists:faculties,id',
            'email' => 'required|email|unique:students,email',
            'password' => 'required|string|min:6',
            'gender' => 'required|in:male,female,other',
        ]);

        $student = Student::create([
            'name' => $request->name,
            'reg_no' => $request->reg_no,
            'year_of_study' => $request->year_of_study,
            'faculty_id' => $request->faculty_id,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'gender' => $request->gender,
        ]);

        $token = $student->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => ['token' => $token, 'student' => $student->load('faculty')]
        ], 201);
    }

    public function requestRegistrationOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:students,email',
                'name' => 'required|string|max:255',
                'reg_no' => 'required|string|unique:students,reg_no',
                'year_of_study' => 'required|integer|between:1,4',
                'faculty_id' => 'required|exists:faculties,id',
                'password' => 'required|string|min:6',
                'gender' => 'required|in:male,female,other',
            ]);
            Log::info('Received OTP request data: ', $request->all());
    
            $email = $request->email;
    
            // Validate faculty_id exists
            if (!Faculty::find($request->faculty_id)) {
                Log::warning('Invalid faculty_id: ' . $request->faculty_id . ' for email: ' . $email);
                return response()->json(['error' => 'Invalid faculty ID'], 400);
            }
    
            // Prepare data for storage
            $data = $request->only([
                'name', 'reg_no', 'year_of_study', 'faculty_id', 'password', 'gender'
            ]);
            Log::info('Prepared OTP data: ', $data);
    
            $jsonData = json_encode($data);
            if ($jsonData === false) {
                Log::error('JSON encoding failed: ' . json_last_error_msg(), ['data' => $data]);
                return response()->json(['error' => 'Failed to encode registration data'], 500);
            }
            Log::info('Encoded JSON data: ', ['jsonData' => $jsonData]);
    
            // Generate 6-digit OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = Carbon::now()->addMinutes(5);
    
            // Store OTP
            $otpRecord = Otp::create([
                'email' => $email,
                'otp' => $otp,
                'expires_at' => $expiresAt,
                'used' => false,
                'data' => $jsonData,
            ]);
            Log::info('Stored OTP record: ', $otpRecord->toArray());
    
            // Send OTP via email
            try {
                Mail::to($email)->send(new OtpMail($otp));
                Log::info('Registration OTP email sent to: ' . $email);
            } catch (\Exception $e) {
                Log::error('Failed to send registration OTP email to ' . $email . ': ' . $e->getMessage());
                return response()->json(['error' => 'Failed to send OTP. Please try again.'], 500);
            }
    
            return response()->json(['message' => 'OTP sent successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Request registration OTP failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function verifyRegistrationOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);
    
            Log::info('Verifying OTP for email: ' . $request->email);
    
            $otpRecord = Otp::where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('used', false)
                ->where('expires_at', '>', Carbon::now())
                ->first();
            Log::info('Retrieved OTP record: ', $otpRecord ? $otpRecord->toArray() : []);
    
            if (!$otpRecord) {
                Log::warning('Invalid or expired OTP for email: ' . $request->email);
                return response()->json(['error' => 'Invalid or expired OTP'], 400);
            }
    
            $data = json_decode($otpRecord->data, true);
            Log::info('Decoded OTP data: ', ['data' => $data]);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error: ' . json_last_error_msg(), ['raw_data' => $otpRecord->data]);
                return response()->json(['error' => 'Invalid OTP data format'], 400);
            }
            if (!$data || !is_array($data) || empty($data)) {
                Log::error('Invalid or empty OTP data for email: ' . $request->email, ['data' => $otpRecord->data]);
                return response()->json(['error' => 'Invalid OTP data'], 400);
            }
    
            // Validate required fields in data
            $requiredFields = ['name', 'reg_no', 'year_of_study', 'faculty_id', 'password', 'gender'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    Log::error("Missing $field in OTP data for email: " . $request->email, ['data' => $data]);
                    return response()->json(['error' => "Missing $field in OTP data"], 400);
                }
            }
    
            // Register the user
            $student = Student::create([
                'name' => $data['name'],
                'reg_no' => $data['reg_no'],
                'year_of_study' => $data['year_of_study'],
                'faculty_id' => $data['faculty_id'],
                'email' => $request->email,
                'password' => bcrypt($data['password']),
                'gender' => $data['gender'],
            ]);
    
            Log::info('Student created: ' . $student->id);
    
            $token = $student->createToken('mobile-app')->plainTextToken;
    
            // Mark OTP as used
            $otpRecord->update(['used' => true]);
    
            // Load faculty relationship safely
            $studentWithFaculty = $student->load(['faculty' => function ($query) {
                $query->select('id', 'name');
            }]);
    
            Log::info('OTP verification successful for email: ' . $request->email);
    
            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => ['token' => $token, 'student' => $studentWithFaculty]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Verify registration OTP failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out successfully'], 200);
    }

    public function profile(Request $request)
    {
        return response()->json(['success' => true, 'data' => $request->user()], 200);
    }

    public function editProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'reg_no' => 'required|string|unique:students,reg_no,' . $request->user()->id,
            'year_of_study' => 'required|integer|between:1,4',
            'email' => 'required|email|unique:students,email,' . $request->user()->id,
            'gender' => 'required|in:male,female,other',
        ]);

        $student = $request->user();
        $student->update($request->only('name', 'reg_no', 'year_of_study', 'email', 'gender'));
        return response()->json(['success' => true, 'message' => 'Profile updated', 'data' => $student], 200);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $student = $request->user();
        if (!Hash::check($request->current_password, $student->password)) {
            return response()->json(['success' => false, 'message' => 'Current password incorrect'], 401);
        }

        $student->password = bcrypt($request->new_password);
        $student->save();
        return response()->json(['success' => true, 'message' => 'Password changed'], 200);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $student = Student::where('email', $request->email)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Email not found'], 404);
        }

        // Handled by OTP flow in OtpController
        return response()->json(['success' => true, 'message' => 'Use OTP flow to reset password'], 200);
    }

    public function storeToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $student = Auth::guard('sanctum')->user();
        if (!$student) {
            return response()->json(['error' => 'Unauthorized'], 401);
}

// Store the FCM token for push notifications
$student->update(['fcm_token' => $request->token]);
return response()->json(['success' => true, 'message' => 'Token stored successfully'], 200);
}

public function updateOnlineStatus(Request $request)
{
$request->validate([
'is_online' => 'required|boolean',
]);

$student = $request->user();
$student->update(['is_online' => $request->is_online]);
return response()->json(['success' => true, 'message' => 'Online status updated'], 200);
}

public function requestOtp(Request $request)
{
$request->validate(['email' => 'required|email']);

$email = $request->email;
$student = Student::where('email', $email)->first();
if (!$student) {
return response()->json(['error' => 'Email not found'], 404);
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
Log::info('Password reset OTP email sent to: ' . $email);
} catch (\Exception $e) {
Log::error('Failed to send password reset OTP email to ' . $email . ': ' . $e->getMessage());
return response()->json(['error' => 'Failed to send OTP. Please try again.'], 500);
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
'password' => 'required|string|min:6',
]);

$student = Student::where('email', $request->email)->first();
if (!$student) {
return response()->json(['error' => 'Email not found'], 404);
}

$student->password = bcrypt($request->password);
$student->save();
return response()->json(['message' => 'Password reset successfully'], 200);
}
}