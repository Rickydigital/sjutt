<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\Program;
use App\Models\Student;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reg_no' => 'required|string',
                'password' => 'required',
                'fcm_token' => 'sometimes|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            // Find the student by email
            $student = Student::where('reg_no', $request->reg_no)->first();
    
            // Check if student exists and password is correct
            if (!$student || !Hash::check($request->password, $student->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                ], 401);
            }
    
            // Generate a Sanctum token
            $token = $student->createToken('mobile-app')->plainTextToken;

            //update the fcm token
            if ($request->has('fcm_token')) {
                $student->update(['fcm_token' => $request->fcm_token]);
            }
    
            return response()->json([
                'status' => 'success',
                'message' => 'login successful',
                'data' => [
                    'token' => $token,
                    'student' => $student
                ]
            ], 200);
    
        } catch (\Exception $e) {
            \Log::error('Login Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Login failed',
            ], 500);
        }
    }

    public function getPrograms()
    {
        $programs = Program::with('faculties')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Programs retrieved successfully',
            'data' => ['programs' => $programs]
        ], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'reg_no' => 'required|string|unique:students,reg_no',
            'faculty_id' => 'required|exists:faculties,id',
            'program_id' => 'required|exists:programs,id',
            'email' => 'required|email|unique:students,email',
            'password' => 'required|string|min:6',
            'gender' => 'required|in:male,female,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Student::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'reg_no' => $request->reg_no,
            'faculty_id' => $request->faculty_id,
            'program_id' => $request->program_id,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'gender' => $request->gender,
        ]);

        $token = $student->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => ['token' => $token, 'student' => $student->load('faculty')]
        ], 201);
    }

    //TODO:  remove Logs
    public function requestRegistrationOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'reg_no' => 'sometimes|string',
                'program_id' => 'sometimes|exists:programs,id',
                'faculty_id' => 'sometimes|exists:faculties,id',
                'password' => 'sometimes|string|min:6',
                'gender' => 'sometimes|in:male,female,other',
                'fcm_token' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('Received OTP request data: ', $request->all());

            $email = $request->email;

            // Check if email exists in students or has a pending OTP
            // $student = Student::where('email', $email)->exists();
            // if (!$student && !$request->has(['first_name', 'last_name', 'reg_no', 'program_id', 'faculty_id', 'password', 'gender'])) {
            //     Log::warning('Email not found and no registration data provided: ' . $email);
            //     //if the user says he want to resend otp but he didnt request it at first
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Email not found or invalid registration data'
            //     ], 404);
            // }

            // Prepare data for storage if new registration
            $data = [];
            // if the request has a first name then its for registration else its for OTP resend
            if ($request->has('first_name')) {
                $data = $request->only(['first_name', 'last_name', 'reg_no', 'program_id', 'faculty_id', 'password', 'gender', 'fcm_token']);
                //hash the password
                $data['password'] = bcrypt($data['password']);
                Log::info('Prepared OTP data for new registration: ', $data);
            } else {
                // For resend, use existing data or minimal info
                $data = ['email' => $email];
                Log::info('Prepared OTP data for resend: ', $data);
            }

            $jsonData = json_encode($data);
            
            //if json encoding fails
            if ($jsonData === false) {
                Log::error('JSON encoding failed: ' . json_last_error_msg(), ['data' => $data]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to encode registration data'
                ], 500);
            }
            Log::info('Encoded JSON data: ', ['jsonData' => $jsonData]);

            // Generate 6-digit OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = Carbon::now()->addMinutes(5);

            if(Otp::where('email', $email)->exists()) {
                Otp::updateOrCreate(
                    ['email' => $email],
                    [
                        'data' => $jsonData,
                        'otp' => $otp,
                        'expires_at' => $expiresAt,
                        'used' => false,
                    ]
                );
            } else {
                Log::info('No existing OTP found for email: ' . $email);
                Otp::updateOrCreate(
                    ['email' => $email],
                    values: [
                        'otp' => $otp,
                        'expires_at' => $expiresAt,
                        'used' => false,
                        'data' => $jsonData,
                    ]
                );
            }
            Log::info('Stored/Updated OTP record for email: ' . $email);

            // Send OTP via email
            try {
                Mail::to($email)->send(new OtpMail($otp));
                Log::info('Registration OTP email sent to: ' . $email);
            } catch (\Exception $e) {
                Log::error('Failed to send registration OTP email to ' . $email . ': ' . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send OTP. Please try again.'
                ], 500);
            }

            //if everything goes well
            return response()->json([
                'status' => 'success',
                'message' => 'OTP sent successfully',
                'data' => ['email' => $email]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Request registration OTP failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Server error'
            ], 500);
        }
    }

   public function verifyRegistrationOtp(Request $request)
{
    try {
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
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        $data = json_decode($otpRecord->data, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid registration data'
            ], 400);
        }

        // Check if reg_no already exists
        $existingStudent = Student::where('reg_no', $data['reg_no'])->first();

        if ($existingStudent) {
            $otpRecord->update(['used' => true]); // prevent OTP reuse

            return response()->json([
                'status' => 'error',
                'message' => 'Reg No already taken. You can reset your password with your registered email or contact your Administrator.'
            ], 422);
        }

        // Optional: also protect against email already registered
        if (Student::where('email', $request->email)->exists()) {
            $otpRecord->update(['used' => true]);
            return response()->json([
                'status' => 'error',
                'message' => 'Reg No already taken. You can reset your password with your registered email or contact your Administrator.'
            ], 422);
        }

        // Create new student
        $student = Student::create([
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'reg_no'      => $data['reg_no'],
            'program_id'  => $data['program_id'],
            'faculty_id'  => $data['faculty_id'],
            'email'       => $request->email,
            'password'    => $data['password'],
            'gender'      => $data['gender'],
            'fcm_token'   => $data['fcm_token'] ?? null,
        ]);

        $token = $student->createToken('mobile-app')->plainTextToken;
        $otpRecord->update(['used' => true]);

        $studentWithFaculty = $student->load(['faculty' => fn($q) => $q->select('id', 'name')]);

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => [
                'token'   => $token,
                'student' => $studentWithFaculty
            ]
        ], 201);

    } catch (\Exception $e) {
        Log::error('Verify registration OTP failed: ' . $e->getMessage(), ['exception' => $e]);

        return response()->json([
            'status'  => 'error',
            'message' => 'Reg No already taken. You can reset your password with your registered email or contact your Administrator.'
        ], 500);
    }
}

    public function logout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:students,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Student::find($request->id);

        if ($student) {
            // This will delete all tokens for the user, logging them out from all devices.
            $student->tokens()->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ], 200);
        }
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Profile retrieved successfully',
            'data' => $request->user()
        ], 200);
    }

    public function editProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:students,id',
            'faculty_id' => 'sometimes|exists:faculties,id',
            'program_id' => 'sometimes|exists:programs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $authenticatedUser = $request->user();
        $userIdToEdit = $request->input('user_id', $authenticatedUser->id);

        if ($userIdToEdit != $authenticatedUser->id) {
            // For now, only allow users to edit their own profile.
            // An admin role check could be added here in the future.
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $studentToEdit = Student::find($userIdToEdit);

        if (!$studentToEdit) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $studentToEdit->update($request->only('faculty_id', 'program_id'));

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => $studentToEdit->load(['faculty', 'program'])
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:students,id',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $authenticatedUser = $request->user();
        $userIdToChange = $request->input('user_id', $authenticatedUser->id);

        if ($userIdToChange != $authenticatedUser->id) {
            // For now, only allow users to change their own password.
            // An admin role check could be added here in the future to allow password resets.
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $studentToChange = Student::find($userIdToChange);

        if (!$studentToChange) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if (!Hash::check($request->current_password, $studentToChange->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password incorrect'
            ], 401);
        }

        $studentToChange->password = bcrypt($request->new_password);
        $studentToChange->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Password changed'
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $student = Student::where('email', $request->email)->first();
        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email not found'
            ], 404);
        }

        // Handled by OTP flow in OtpController
        return response()->json([
            'status' => 'success',
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
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $student->update(['fcm_token' => $request->token]);
        return response()->json([
            'status' => 'success',
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
            'status' => 'success',
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
                'status' => 'error',
                'message' => 'Email not found'
            ], 404);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'OTP sent successfully',
            'data' => ['email' => $email]
        ], 200);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        $otpRecord->update(['used' => true]);
        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified successfully'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $student = Student::where('email', $request->email)->first();
        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email not found'
            ], 404);
        }

        $student->password = bcrypt($request->password);
        $student->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successfully'
        ], 200);
    }
}