<?php

namespace App\Http\Controllers\Mobile\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Services\ImageService;
use App\Models\AlumniEducation;
use App\Models\AlumniEmployment;
use App\Models\Country;
use App\Models\EmploymentYear;
use App\Models\GraduationYear;
use App\Models\Otp;
use App\Notifications\OtpNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AlumniAuthController extends Controller
{
    /**
     * 1) First app step: check email and tell mobile app which screen to open.
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $alumni = Alumni::where('email', strtolower(trim($request->email)))->first();

        if (!$alumni) {
            // Self-registration disabled — accounts are seeded by admin only.
            // return response()->json([
            //     'status' => 'success',
            //     'action' => 'register',
            //     'message' => 'No alumni account found. Please complete registration.',
            //     'data' => ['email' => strtolower(trim($request->email))],
            // ]);
            return response()->json([
                'status' => 'error',
                'message' => 'No alumni account found. Please contact the Students\' Union.',
            ], 404);
        }

        if ($alumni->status === 'suspended') {
            return response()->json([
                'status' => 'error',
                'action' => 'suspended',
                'message' => 'Your alumni account has been suspended. Please contact administration.',
            ], 403);
        }

        if (!$alumni->is_active) {
            return response()->json([
                'status' => 'success',
                'action' => 'first_login',
                'message' => 'Account found. Please use your temporary password and create a new password.',
                'data' => [
                    'email' => $alumni->email,
                    'alumni' => $this->formatAlumni($alumni),
                    'profile_complete' => $this->isProfileComplete($alumni),
                    'requires_profile_update' => !$this->isProfileComplete($alumni),
                    'missing_fields' => $this->missingProfileFields($alumni),
                ],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'action' => 'login',
            'message' => 'Account already active. Please login or use forgot password.',
            'data' => [
                'email' => $alumni->email,
            ],
        ]);
    }

    /**
     * 2) New alumni self registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), $this->profileValidationRules($request, null, true) + [
            'email' => ['required', 'email', 'unique:alumni,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $alumni = DB::transaction(function () use ($request) {
            $photoPath = $this->storeProfilePhoto($request);

            $alumni = Alumni::create([
                'f_name' => $request->f_name,
                'm_name' => $request->m_name,
                'l_name' => $request->l_name,
                'email' => strtolower(trim($request->email)),
                'password' => Hash::make($request->password),
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'profile_photo' => $photoPath,
                'nida_number' => $request->nida_number,
                'settlement_country_id' => $request->settlement_country_id,
                'settlement_region' => $request->settlement_region,
                'settlement_city' => $request->settlement_city,
                'interested_meetings' => $request->boolean('interested_meetings'),
                'interested_social_platform' => $request->boolean('interested_social_platform'),
                'status' => 'active',
                'is_active' => true,
                'first_login_at' => now(),
                'password_changed_at' => now(),
                'last_login_at' => now(),
            ]);

            $this->syncEducations($alumni, $request);
            $this->syncEmployments($alumni, $request);
            $this->syncSocialPlatforms($alumni, $request);

            return $alumni->fresh($this->profileRelations());
        });

        $token = $alumni->createToken('alumni-mobile-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Alumni registration completed successfully.',
            'data' => $this->authPayload($alumni, $token),
        ], 201);
    }

    /**
     * 3) Imported/pending alumni first login.
     * This activates account, changes temporary password, returns token + profile completeness.
     */
    public function firstLogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'temporary_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'fcm_token' => ['sometimes', 'nullable', 'string'],
        ]);

        $alumni = Alumni::where('email', strtolower(trim($request->email)))->first();

        if (!$alumni) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alumni account not found.',
            ], 404);
        }

        if ($alumni->status === 'suspended') {
            return response()->json([
                'status' => 'error',
                'message' => 'Your alumni account has been suspended. Please contact administration.',
            ], 403);
        }

        if ($alumni->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is already active. Please login or use forgot password.',
            ], 422);
        }

        // if (!Hash::check($request->temporary_password, $alumni->password)) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Temporary password is incorrect.',
        //     ], 401);
        // }

        $alumni->update([
            'password' => Hash::make($request->password),
            'status' => 'active',
            'is_active' => true,
            'first_login_at' => $alumni->first_login_at ?: now(),
            'password_changed_at' => now(),
            'last_login_at' => now(),
            'fcm_token' => $request->fcm_token ?? $alumni->fcm_token ?? null,
        ]);

        $alumni = $alumni->fresh($this->profileRelations());
        $token = $alumni->createToken('alumni-mobile-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => $this->isProfileComplete($alumni)
                ? 'First login successful.'
                : 'First login successful. Please complete your profile.',
            'data' => $this->authPayload($alumni, $token),
        ]);
    }

    /**
     * 4) Normal login. Only active alumni can login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'fcm_token' => ['sometimes', 'nullable', 'string'],
        ]);

        $alumni = Alumni::where('email', strtolower(trim($request->email)))->first();

        if (!$alumni || !Hash::check($request->password, $alumni->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($alumni->status === 'suspended') {
            return response()->json([
                'status' => 'error',
                'message' => 'Your alumni account has been suspended. Please contact administration.',
            ], 403);
        }

        if (!$alumni->is_active || $alumni->status !== 'active') {
            // Return 200 so the mobile client can detect this case without httpPost swallowing it
            return response()->json([
                'status' => 'first_login',
                'action' => 'first_login',
                'message' => 'Please activate your account using your temporary password.',
                'data' => ['email' => $alumni->email],
            ]);
        }

        $alumni->update([
            'last_login_at' => now(),
            'fcm_token' => $request->fcm_token ?? $alumni->fcm_token ?? null,
        ]);

        $alumni = $alumni->fresh($this->profileRelations());
        $token = $alumni->createToken('alumni-mobile-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful.',
            'data' => $this->authPayload($alumni, $token),
        ]);
    }

    /**
     * 5) Authenticated profile.
     */
    public function profile(Request $request)
    {
        $alumni = $request->user()->load($this->profileRelations());

        return response()->json([
            'status' => 'success',
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'alumni' => $this->formatAlumni($alumni),
                'profile_complete' => $this->isProfileComplete($alumni),
                'requires_profile_update' => !$this->isProfileComplete($alumni),
                'missing_fields' => $this->missingProfileFields($alumni),
            ],
        ]);
    }

    /**
     * 6) Complete/update profile after first login or later.
     */
    public function updateProfile(Request $request)
    {
        $alumni = $request->user();

        $validator = Validator::make($request->all(), $this->profileValidationRules($request, $alumni, false));

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $alumni) {
            $update = $request->only([
                'f_name',
                'm_name',
                'l_name',
                'date_of_birth',
                'gender',
                'phone',
                'nida_number',
                'settlement_country_id',
                'settlement_region',
                'settlement_city',
            ]);

            if ($request->has('interested_meetings')) {
                $update['interested_meetings'] = $request->boolean('interested_meetings');
            }

            if ($request->has('interested_social_platform')) {
                $update['interested_social_platform'] = $request->boolean('interested_social_platform');
            }

            if ($request->hasFile('profile_photo')) {
                if ($alumni->profile_photo) {
                    Storage::disk('public')->delete($alumni->profile_photo);
                }
                $update['profile_photo'] = $this->storeProfilePhoto($request);
            }

            $alumni->update($update);

            $this->syncEducations($alumni, $request);
            $this->syncEmployments($alumni, $request);
            $this->syncSocialPlatforms($alumni, $request);
        });

        $alumni = $alumni->fresh($this->profileRelations());

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => [
                'alumni' => $this->formatAlumni($alumni),
                'profile_complete' => $this->isProfileComplete($alumni),
                'requires_profile_update' => !$this->isProfileComplete($alumni),
                'missing_fields' => $this->missingProfileFields($alumni),
            ],
        ]);
    }

    /**
     * 7) Change password while logged in.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $alumni = $request->user();

        if (!Hash::check($request->current_password, $alumni->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect.',
            ], 401);
        }

        $alumni->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
            'is_active' => true,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * 8) Forgot password - send OTP.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $alumni = Alumni::where('email', strtolower(trim($request->email)))->first();

        if ($alumni && $alumni->status !== 'suspended') {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            Otp::updateOrCreate(
                ['email' => $alumni->email],
                [
                    'otp' => $otp,
                    'data' => json_encode([
                        'purpose' => 'reset_password_alumni',
                        'alumni_id' => $alumni->id,
                    ]),
                    'expires_at' => now()->addMinutes(10),
                    'used' => false,
                ]
            );

            $alumni->notify(new OtpNotification($otp, 'Reset Your SJUT Alumni Password'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'If an alumni account exists, an OTP has been sent.',
        ]);
    }

    /**
     * 9) Verify reset OTP and return temporary reset token.
     */
    public function verifyResetPasswordOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $otpRecord = Otp::where('email', strtolower(trim($request->email)))
            ->where('otp', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP.',
            ], 400);
        }

        $data = json_decode($otpRecord->data, true);

        if (($data['purpose'] ?? null) !== 'reset_password_alumni') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP purpose.',
            ], 400);
        }

        $alumni = Alumni::findOrFail($data['alumni_id']);
        $otpRecord->update(['used' => true]);

        $resetToken = $alumni->createToken('reset-password-temp-alumni', ['reset-password'], now()->addMinutes(15))->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified successfully.',
            'data' => [
                'reset_token' => $resetToken,
                'expires_in_minutes' => 15,
            ],
        ]);
    }

    /**
     * 10) Reset password. Call using reset token as Bearer token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $alumni = $request->user();

        $token = $alumni?->currentAccessToken();
        if (!$token || !in_array('reset-password', $token->abilities ?? [], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid reset token.',
            ], 403);
        }

        $alumni->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'is_active' => true,
            'status' => 'active',
        ]);

        $token->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successfully. Please login.',
        ]);
    }

    /**
     * 11) FCM token save.
     */
    public function storeToken(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $request->user()->update(['fcm_token' => $request->token]);

        return response()->json([
            'status' => 'success',
            'message' => 'FCM token stored successfully.',
        ]);
    }

    /**
     * 12) Logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    private function authPayload(Alumni $alumni, string $token): array
    {
        return [
            'token' => $token,
            'alumni' => $this->formatAlumni($alumni),
            'profile_complete' => $this->isProfileComplete($alumni),
            'requires_profile_update' => !$this->isProfileComplete($alumni),
            'missing_fields' => $this->missingProfileFields($alumni),
        ];
    }

    private function formatAlumni(Alumni $alumni): array
    {
        $alumni->loadMissing($this->profileRelations());

        return [
            'id' => $alumni->id,
            'f_name' => $alumni->f_name,
            'm_name' => $alumni->m_name,
            'l_name' => $alumni->l_name,
            'full_name' => trim($alumni->f_name . ' ' . $alumni->m_name . ' ' . $alumni->l_name),
            'email' => $alumni->email,
            'phone' => $alumni->phone,
            'date_of_birth' => optional($alumni->date_of_birth)->format('Y-m-d'),
            'gender' => $alumni->gender,
            'profile_photo' => $alumni->profile_photo ? asset('storage/' . $alumni->profile_photo) : null,
            'nida_number' => $alumni->nida_number,
            'settlement_country_id' => $alumni->settlement_country_id,
            'settlement_country' => $alumni->country,
            'settlement_region' => $alumni->settlement_region,
            'settlement_city' => $alumni->settlement_city,
            'interested_meetings' => (bool) $alumni->interested_meetings,
            'interested_social_platform' => (bool) $alumni->interested_social_platform,
            'status' => $alumni->status,
            'is_active' => (bool) $alumni->is_active,
            'first_login_at' => optional($alumni->first_login_at)->toDateTimeString(),
            'last_login_at' => optional($alumni->last_login_at)->toDateTimeString(),
            'password_changed_at' => optional($alumni->password_changed_at)->toDateTimeString(),
            'educations' => $alumni->educations,
            'employments' => $alumni->employments,
            'social_platforms' => $alumni->socialPlatforms,
        ];
    }

    private function profileRelations(): array
    {
        return [
            'country',
            'educations.faculty',
            'educations.program',
            'educations.graduationYear',
            'employments.employmentState',
            'employments.employmentSector',
            'employments.employmentYear',
            'socialPlatforms',
        ];
    }

    private function missingProfileFields(Alumni $alumni): array
    {
        $alumni->loadMissing(['educations', 'employments']);

        $missing = [];

        foreach (['f_name', 'l_name', 'email', 'phone', 'date_of_birth', 'gender', 'profile_photo', 'settlement_country_id', 'settlement_region', 'settlement_city'] as $field) {
            if (blank($alumni->{$field})) {
                $missing[] = $field;
            }
        }

        if ($alumni->educations->isEmpty()) {
            $missing[] = 'education_details';
        }

        if ($alumni->employments->isEmpty()) {
            $missing[] = 'employment_details';
        }

        return $missing;
    }

    private function isProfileComplete(Alumni $alumni): bool
    {
        return count($this->missingProfileFields($alumni)) === 0;
    }

    private function profileValidationRules(Request $request, ?Alumni $alumni = null, bool $required = false): array
    {
        $requiredRule = $required ? 'required' : 'sometimes';
        $alumniId = $alumni?->id;

        return [
            'f_name' => [$requiredRule, 'string', 'max:255'],
            'm_name' => ['nullable', 'string', 'max:255'],
            'l_name' => [$requiredRule, 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'nida_number' => ['nullable', 'string', 'max:30', Rule::unique('alumni', 'nida_number')->ignore($alumniId)],
            'settlement_country_id' => ['nullable', 'exists:countries,id'],
            'settlement_region' => ['nullable', 'string', 'max:255'],
            'settlement_city' => ['nullable', 'string', 'max:255'],
            'interested_meetings' => ['nullable', 'boolean'],
            'interested_social_platform' => ['nullable', 'boolean'],

            'educations' => ['nullable', 'array'],
            'educations.*.faculty_id' => ['nullable', 'exists:faculties,id'],
            'educations.*.program_id' => ['nullable', 'exists:programs,id'],
            'educations.*.graduation_year' => ['nullable', 'integer', 'digits:4'],
            'educations.*.degree_program_major' => ['nullable', 'string', 'max:255'],

            'employments' => ['nullable', 'array'],
            'employments.*.employment_state_id' => ['nullable', 'exists:employment_states,id'],
            'employments.*.employment_sector_id' => ['nullable', 'exists:employment_sectors,id'],
            'employments.*.employment_year' => ['nullable', 'integer', 'digits:4'],
            'employments.*.organization' => ['nullable', 'string', 'max:255'],
            'employments.*.is_current' => ['nullable', 'boolean'],

            'social_platform_ids' => ['nullable', 'array'],
            'social_platform_ids.*' => ['integer', 'exists:social_platforms,id'],
        ];
    }

    private function storeProfilePhoto(Request $request): ?string
    {
        if (!$request->hasFile('profile_photo')) {
            return null;
        }

        return ImageService::storeAsWebP(
            $request->file('profile_photo'),
            'alumni/profile_photos',
            quality: 85,
            maxWidth: 800
        );
    }

    private function syncEducations(Alumni $alumni, Request $request): void
    {
        if (!$request->has('educations')) {
            return;
        }

        $alumni->educations()->delete();

        foreach ($request->input('educations', []) as $education) {
            $graduationYearId = null;

            if (!empty($education['graduation_year'])) {
                $graduationYearId = GraduationYear::firstOrCreate([
                    'year' => $education['graduation_year'],
                ])->id;
            }

            AlumniEducation::create([
                'alumni_id' => $alumni->id,
                'faculty_id' => $education['faculty_id'] ?? null,
                'program_id' => $education['program_id'] ?? null,
                'graduation_year_id' => $graduationYearId,
                'degree_program_major' => $education['degree_program_major'] ?? null,
            ]);
        }
    }

    private function syncEmployments(Alumni $alumni, Request $request): void
    {
        if (!$request->has('employments')) {
            return;
        }

        $alumni->employments()->delete();

        foreach ($request->input('employments', []) as $employment) {
            $employmentYearId = null;

            if (!empty($employment['employment_year'])) {
                $employmentYearId = EmploymentYear::firstOrCreate([
                    'year' => $employment['employment_year'],
                ])->id;
            }

            AlumniEmployment::create([
                'alumni_id' => $alumni->id,
                'employment_state_id' => $employment['employment_state_id'] ?? null,
                'employment_sector_id' => $employment['employment_sector_id'] ?? null,
                'employment_year_id' => $employmentYearId,
                'organization' => $employment['organization'] ?? null,
                'is_current' => (bool) ($employment['is_current'] ?? true),
            ]);
        }
    }

    private function syncSocialPlatforms(Alumni $alumni, Request $request): void
    {
        if (!$request->has('social_platform_ids')) {
            return;
        }

        $alumni->socialPlatforms()->sync($request->input('social_platform_ids', []));
    }
}
