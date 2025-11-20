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

class AuthController extends Controller {
    public function login( Request $request ) {
        $request->validate( [
            'reg_no' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'sometimes|string',
        ] );

        $student = Student::where( 'reg_no', $request->reg_no )->first();

        if ( !$student || !Hash::check( $request->password, $student->password ) ) {
            return response()->json( [
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401 );
        }

        // Update FCM token
        if ( $request->filled( 'fcm_token' ) ) {
            $student->update( [ 'fcm_token' => $request->fcm_token ] );
        }

        $token = $student->createToken( 'mobile-app' )->plainTextToken;

        // Check if profile is incomplete
        $isComplete = $student->status === 'Active'
        && $student->program_id
        && $student->faculty_id
        && $student->phone;

        return response()->json( [
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [ 'token' => $token,
            'student' => $student->load( 'faculty', 'program' ),
            'profile_complete' => $isComplete,
            'requires_update' => !$isComplete, ]
        ] );
    }

    public function getPrograms() {
        $programs = Program::with( 'faculties' )->get();
        return response()->json( [
            'status' => 'success',
            'message' => 'Programs retrieved successfully',
            'data' => [ 'programs' => $programs ]
        ], 200 );
    }

    public function register( Request $request ) {
        $request->validate( [
            'student_id' => 'required|exists:students,id',
            'email'      => 'required|email',
            'phone'      => 'required|string|max:20',
            'gender'     => 'required|in:male,female',
            'password'   => 'required|string|min:6|confirmed',
            'faculty_id' => 'required|exists:faculties,id',
            'program_id' => 'required|exists:programs,id',
            'status'     => 'required|in:Active,Alumni',
        ] );

        $student = Student::findOrFail( $request->student_id );

        if ( $student->status === 'Active' && $student->phone && $student->email ) {
            return response()->json( [
                'status'  => 'error',
                'message' => 'Profile already completed'
            ], 400 );
        }

        if ( Student::where( 'email', $request->email )->where( 'id', '!=', $student->id )->exists() ) {
            return response()->json( [
                'status'  => 'error',
                'message' => 'Email already taken'
            ], 422 );
        }

        $student->update( [
            'email'      => $request->email,
            'phone'      => $request->phone,
            'gender'     => $request->gender,
            'password'   => bcrypt( $request->password ),
            'faculty_id' => $request->faculty_id,
            'program_id' => $request->program_id,
            'status'     => $request->status,
        ] );

        $token = $student->createToken( 'mobile-app' )->plainTextToken;

        return response()->json( [
            'status'           => 'success',
            'message'          => 'Profile completed successfully',
            'token'            => $token,
            'student'          => $student->fresh()->load( 'faculty', 'program' ),
            'profile_complete' => true,
            'user_type'        => $request->status
        ], 201 );
    }

    public function requestRegistrationOtp( Request $request ) {
        $request->validate( [
            'student_id' => 'required|exists:students,id',
            'email'      => 'required|email',
            'phone'      => 'required|string|max:20',
            'gender'     => 'required|in:male,female',
            'password'   => 'required|string|min:6',
            'faculty_id' => 'required|exists:faculties,id',
            'program_id' => 'required|exists:programs,id',
            'status'     => 'required|in:Active,Alumni',
        ] );

        $student = Student::findOrFail( $request->student_id );

        if ( $student->status === 'Active' && $student->phone && $student->email ) {
            return response()->json( [
                'status'  => 'error',
                'message' => 'Profile already completed'
            ], 400 );
        }

        if ( Student::where( 'email', $request->email )->where( 'id', '!=', $student->id )->exists() ) {
            return response()->json( [
                'status'  => 'error',
                'message' => 'Email already in use'
            ], 422 );
        }

        $data = [
            'student_id' => $student->id,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'gender'     => $request->gender,
            'password'   => bcrypt( $request->password ),
            'faculty_id' => $request->faculty_id,
            'program_id' => $request->program_id,
            'status'     => $request->status,
        ];

        $otp = str_pad( rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );

        Otp::updateOrCreate(
            [ 'email' => $request->email ],
            [
                'otp'        => $otp,
                'data'       => json_encode( $data ),
                'expires_at' => now()->addMinutes( 10 ),
                'used'       => false,
            ]
        );

        Mail::to( $request->email )->send( new OtpMail( $otp ) );

        return response()->json( [
            'status'  => 'success',
            'message' => 'OTP sent to your email',
            'email'   => $request->email
        ] );
    }

    public function resendRegistrationOtp( Request $request ) {
        $request->validate( [ 'email' => 'required|email' ] );
        $email = $request->email;
        $otpRecord = Otp::where( 'email', $email )->where( 'used', false )->first();

        if ( !$otpRecord ) {
            return response()->json( [
                'status' => 'error',
                'message' => 'Invalid data, Please register again.'
            ], 500 );
        }

        // Generate 6-digit OTP
        $otp = str_pad( rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
        $expiresAt = Carbon::now()->addMinutes( 5 );

        //update the otp
        $otpRecord->update( [
            'otp' => $otp,
            'expires_at' => $expiresAt,
        ] );

        //send response
        Mail::to( $request->email )->send( new OtpMail( $otp ) );

        return response()->json( [
            'status' => 'success',
            'message' => 'OTP sent successfully',
            'data' => [ 'email' => $email ]
        ], 200 );

    }

    public function verifyRegistrationOtp( Request $request ) {
        $request->validate( [
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ] );

        $otpRecord = Otp::where( 'email', $request->email )
        ->where( 'otp', $request->otp )
        ->where( 'used', false )
        ->where( 'expires_at', '>', now() )
        ->first();

        if ( !$otpRecord ) {
            return response()->json( [
                'status'  => 'error',
                'message' => 'Invalid or expired OTP'
            ], 400 );
        }

        $data = json_decode( $otpRecord->data, true );
        $student = Student::findOrFail( $data[ 'student_id' ] );

        $student->update( [
            'email'      => $data[ 'email' ],
            'phone'      => $data[ 'phone' ],
            'gender'     => $data[ 'gender' ],
            'password'   => $data[ 'password' ],
            'faculty_id' => $data[ 'faculty_id' ],
            'program_id' => $data[ 'program_id' ],
            'status'     => $data[ 'status' ],
        ] );

        $otpRecord->update( [ 'used' => true ] );

        // $token = $student->createToken( 'mobile-app' )->plainTextToken;

        return response()->json( [
            'status'           => 'success',
            'message'          => 'Account activated successfully!',
            'data' => [
                'student'          => $student->load( 'faculty', 'program' ),
                'profile_complete' => true,
                'user_type'        => $data[ 'status' ] ]
            ], 201 );
        }

        public function logout( Request $request ) {
            $validator = Validator::make( $request->all(), [
                'id' => 'required|integer|exists:students,id',
            ] );

            if ( $validator->fails() ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422 );
            }

            $student = Student::find( $request->id );

            if ( $student ) {
                // This will delete all tokens for the user, logging them out from all devices.
                $student->tokens()->delete();
                return response()->json( [
                    'status' => 'success',
                    'message' => 'Logged out successfully'
                ], 200 );
            }
        }

        public function profile( Request $request ) {
            return response()->json( [
                'status' => 'success',
                'message' => 'Profile retrieved successfully',
                'data' => $request->user()
            ], 200 );
        }

        public function editProfile( Request $request ) {
            $validator = Validator::make( $request->all(), [
                'user_id' => 'sometimes|exists:students,id',
                'faculty_id' => 'sometimes|exists:faculties,id',
                'program_id' => 'sometimes|exists:programs,id',
            ] );

            if ( $validator->fails() ) {
                return response()->json( [ 'status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors() ], 422 );
            }

            $authenticatedUser = $request->user();
            $userIdToEdit = $request->input( 'user_id', $authenticatedUser->id );

            if ( $userIdToEdit != $authenticatedUser->id ) {
                // For now, only allow users to edit their own profile.
                // An admin role check could be added here in the future.
                return response()->json( [ 'status' => 'error', 'message' => 'Unauthorized' ], 403 );
            }

            $studentToEdit = Student::find( $userIdToEdit );

            if ( !$studentToEdit ) {
                return response()->json( [ 'status' => 'error', 'message' => 'User not found' ], 404 );
            }

            $studentToEdit->update( $request->only( 'faculty_id', 'program_id' ) );

            return response()->json( [
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $studentToEdit->load( [ 'faculty', 'program' ] )
            ], 200 );
        }

        public function changePassword( Request $request ) {
            $validator = Validator::make( $request->all(), [
                'user_id' => 'sometimes|exists:students,id',
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ] );

            if ( $validator->fails() ) {
                return response()->json( [ 'status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors() ], 422 );
            }

            $authenticatedUser = $request->user();
            $userIdToChange = $request->input( 'user_id', $authenticatedUser->id );

            if ( $userIdToChange != $authenticatedUser->id ) {
                // For now, only allow users to change their own password.
                // An admin role check could be added here in the future to allow password resets.
                return response()->json( [ 'status' => 'error', 'message' => 'Unauthorized' ], 403 );
            }

            $studentToChange = Student::find( $userIdToChange );

            if ( !$studentToChange ) {
                return response()->json( [ 'status' => 'error', 'message' => 'User not found' ], 404 );
            }

            if ( !Hash::check( $request->current_password, $studentToChange->password ) ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => 'Current password incorrect'
                ], 401 );
            }

            $studentToChange->password = bcrypt( $request->new_password );
            $studentToChange->save();
            return response()->json( [
                'status' => 'success',
                'message' => 'Password changed'
            ], 200 );
        }

        public function forgotPassword( Request $request ) {
            $request->validate( [ 'email' => 'required|email' ] );
            $student = Student::where( 'email', $request->email )->first();
            if ( !$student ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => 'Email not found'
                ], 404 );
            }

            // Handled by OTP flow in OtpController
            return response()->json( [
                'status' => 'success',
                'message' => 'Use OTP flow to reset password'
            ], 200 );
        }

        public function storeToken( Request $request ) {
            $request->validate( [
                'token' => 'required|string',
            ] );

            $student = Auth::guard( 'sanctum' )->user();
            if ( !$student ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401 );
            }

            $student->update( [ 'fcm_token' => $request->token ] );
            return response()->json( [
                'status' => 'success',
                'message' => 'Token stored successfully'
            ], 200 );
        }

        public function updateOnlineStatus( Request $request ) {
            $request->validate( [
                'is_online' => 'required|boolean',
            ] );

            $student = $request->user();
            $student->update( [ 'is_online' => $request->is_online ] );
            return response()->json( [
                'status' => 'success',
                'message' => 'Online status updated'
            ], 200 );
        }

        public function requestOtp( Request $request ) {
            $request->validate( [ 'email' => 'required|email' ] );

            $email = $request->email;
            $student = Student::where( 'email', $email )->first();
            if ( !$student ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => 'Email not found'
                ], 404 );
            }

            // Generate 6-digit OTP
            $otp = str_pad( rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
            $expiresAt = Carbon::now()->addMinutes( 5 );

            // Store OTP
            Otp::create( [
                'email' => $email,
                'otp' => $otp,
                'expires_at' => $expiresAt,
                'used' => false,
            ] );

            // Send OTP via email
            try {
                Mail::to( $email )->send( new OtpMail( $otp ) );
                Log::info( 'Password reset OTP email sent to: ' . $email );
            } catch ( \Exception $e ) {
                Log::error( 'Failed to send password reset OTP email to ' . $email . ': ' . $e->getMessage() );
                return response()->json( [
                    'status' => 'error',
                    'message' => 'Failed to send OTP. Please try again.'
                ], 500 );
            }

            return response()->json( [
                'status' => 'success',
                'message' => 'OTP sent successfully',
                'data' => [ 'email' => $email ]
            ], 200 );
        }

        public function verifyOtp( Request $request ) {
            $request->validate( [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ] );

            $otpRecord = Otp::where( 'email', $request->email )
            ->where( 'otp', $request->otp )
            ->where( 'used', false )
            ->where( 'expires_at', '>', Carbon::now() )
            ->first();

            if ( !$otpRecord ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ], 400 );
            }

            $otpRecord->update( [ 'used' => true ] );
            return response()->json( [
                'status' => 'success',
                'message' => 'OTP verified successfully'
            ], 200 );
        }

        // 1. Request Reset Password OTP → stores pending password reset in `data`
public function forgetPassword(Request $request)
{
    $request->validate(['email' => 'required|email']);

    $student = Student::where('email', $request->email)->first();

    // ALWAYS generic message → no email enumeration
    if ($student) {
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // EXACT same logic as registration: store purpose + student_id in `data`
        Otp::updateOrCreate(
            ['email' => $request->email],
            [
                'otp'        => $otp,
                'data'       => json_encode([
                    'purpose'     => 'reset_password',
                    'student_id'  => $student->id,
                ]),
                'expires_at' => now()->addMinutes(10),
                'used'       => false,
            ]
        );

        Mail::to($request->email)->send(new OtpMail($otp));
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'If an account with that email exists, an OTP has been sent.'
    ]);
}

// 2. Verify OTP → same as verifyRegistrationOtp()
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

    // Must be a password reset OTP
    if (!isset($data['purpose']) || $data['purpose'] !== 'reset_password') {
        return response()->json([
            'status'  => 'error',
            'message' => 'Invalid OTP purpose.'
        ], 400);
    }

    $student = Student::findOrFail($data['student_id']);

    // Mark OTP as used
    $otpRecord->update(['used' => true]);

    // Issue temporary 15-minute token
    $tempToken = $student->createToken('reset-password-temp', ['*'], now()->addMinutes(15))
        ->plainTextToken;

    return response()->json([
        'status'  => 'success',
        'message' => 'OTP verified successfully.',
        'data'    => [
            'token'   => $tempToken,
            'student' => [
                'id'         => $student->id,
                'firstName'  => $student->first_name ?? $student->firstName ?? '',
                'lastName'   => $student->last_name ?? $student->lastName ?? '',
                'regNo'      => $student->reg_no,
                'email'      => $student->email,
                'phone'      => $student->phone,
                'gender'     => $student->gender,
                'status'     => $student->status,
                'programId'  => $student->program_id,
                'facultyId'  => $student->faculty_id,
            ]
        ]
    ]);
}

// 3. Reset Password → protected by temp token
public function resetPassword(Request $request)
{
    $request->validate([
        'password'              => 'required|string|min:8|confirmed',
        'password_confirmation' => 'required',
    ]);

    $student = $request->user(); // from sanctum

    if (!$student) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Unauthorized or token expired.'
        ], 401);
    }

    // Update password
    $student->password = bcrypt($request->password);
    $student->save();

    // Delete ONLY the temporary reset token
    $student->tokens()->where('name', 'reset-password-temp')->delete();

    // Issue fresh permanent login token
    $newToken = $student->createToken('mobile-app')->plainTextToken;

    return response()->json([
        'status'  => 'success',
        'message' => 'Password reset successfully.',
        'data'    => [
            'token' => $newToken
        ]
    ]);
}
    }