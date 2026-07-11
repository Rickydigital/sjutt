<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\Alumni;
use App\Models\Country;
use App\Models\EmploymentSector;
use App\Models\EmploymentState;
use App\Models\EmploymentYear;
use App\Models\Faculty;
use App\Models\GraduationYear;
use App\Models\Program;
use App\Models\SocialPlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Notifications\AlumniPasswordResetNotification;
use App\Notifications\AlumniTemporaryPasswordNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AlumniController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view alumni|manage alumni')->only(['index', 'show']);
        $this->middleware('permission:manage alumni|create alumni')->only(['store']);
        $this->middleware('permission:manage alumni|edit alumni')->only(['update']);
        $this->middleware('permission:manage alumni|import alumni')->only(['import']);
        $this->middleware('permission:manage alumni|activate alumni')->only(['activate']);
        $this->middleware('permission:manage alumni')->only(['resendTempPassword', 'sendResetLink']);
        $this->middleware('permission:manage alumni|deactivate alumni')->only(['deactivate']);
        $this->middleware('permission:manage alumni|suspend alumni')->only(['suspend']);
        $this->middleware('permission:manage alumni')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Alumni::query()
            ->with(['country', 'educations.faculty', 'educations.program', 'educations.graduationYear', 'employments.employmentState', 'employments.employmentSector'])
            ->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('f_name', 'like', "%{$search}%")
                    ->orWhere('m_name', 'like', "%{$search}%")
                    ->orWhere('l_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nida_number', 'like', "%{$search}%")
                    ->orWhere('organization', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        if ($request->filled('faculty_id')) {
            $query->whereHas('educations', fn ($q) => $q->where('faculty_id', $request->faculty_id));
        }

        if ($request->filled('program_id')) {
            $query->whereHas('educations', fn ($q) => $q->where('program_id', $request->program_id));
        }

        if ($request->filled('graduation_year_id')) {
            $query->whereHas('educations', fn ($q) => $q->where('graduation_year_id', $request->graduation_year_id));
        }

        $alumni = $query->paginate(25)->withQueryString();

        return view('alumni.index', array_merge($this->formData(), compact('alumni')));
    }

    public function show(Alumni $alumnus)
    {
        $alumnus->load(['country', 'educations.faculty', 'educations.program', 'educations.graduationYear', 'employments.employmentState', 'employments.employmentSector', 'employments.employmentYear', 'socialPlatforms']);

        return view('alumni.show', compact('alumnus'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateAlumni($request);

        // Default temp password = lowercase last name; admin can supply their own
        $plainPassword = $validated['password'] ?? strtolower($validated['l_name']);

        $alumnus = DB::transaction(function () use ($validated, $request, $plainPassword) {
            $photoPath = $request->hasFile('profile_photo')
                ? $request->file('profile_photo')->store('alumni/photos', 'public')
                : null;

            $alumnus = Alumni::create([
                'f_name' => $validated['f_name'],
                'm_name' => $validated['m_name'] ?? null,
                'l_name' => $validated['l_name'],
                'email' => $validated['email'],
                'password' => Hash::make($plainPassword),
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'profile_photo' => $photoPath,
                'nida_number' => $validated['nida_number'] ?? null,
                'settlement_country_id' => $validated['settlement_country_id'] ?? null,
                'settlement_region' => $validated['settlement_region'] ?? null,
                'settlement_city' => $validated['settlement_city'] ?? null,
                'interested_meetings' => $request->boolean('interested_meetings'),
                'interested_social_platform' => $request->boolean('interested_social_platform'),
                'status' => $validated['status'] ?? 'pending',
                'is_active' => $request->boolean('is_active'),
                'password_changed_at' => $request->boolean('is_active') ? now() : null,
            ]);

            $this->syncEducationAndEmployment($alumnus, $validated, $request);

            return $alumnus;
        });

        try {
            $alumnus->notify(new AlumniTemporaryPasswordNotification($plainPassword));
            $alumnus->update(['temporary_password_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Failed to send alumni temp password email to ' . $alumnus->email . ': ' . $e->getMessage());
        }

        return back()->with('success', 'Alumni created and credentials emailed successfully.');
    }

    public function update(Request $request, Alumni $alumnus)
    {
        $validated = $this->validateAlumni($request, $alumnus);

        DB::transaction(function () use ($validated, $request, $alumnus) {
            $data = [
                'f_name' => $validated['f_name'],
                'm_name' => $validated['m_name'] ?? null,
                'l_name' => $validated['l_name'],
                'email' => $validated['email'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'nida_number' => $validated['nida_number'] ?? null,
                'settlement_country_id' => $validated['settlement_country_id'] ?? null,
                'settlement_region' => $validated['settlement_region'] ?? null,
                'settlement_city' => $validated['settlement_city'] ?? null,
                'interested_meetings' => $request->boolean('interested_meetings'),
                'interested_social_platform' => $request->boolean('interested_social_platform'),
                'status' => $validated['status'] ?? $alumnus->status,
                'is_active' => $request->boolean('is_active'),
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
                $data['password_changed_at'] = now();
            }

            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = $request->file('profile_photo')->store('alumni/photos', 'public');
            }

            $alumnus->update($data);
            $this->syncEducationAndEmployment($alumnus, $validated, $request);
        });

        return back()->with('success', 'Alumni updated successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            Excel::import(new AlumniImport, $request->file('file'));
            return back()->with('success', 'Alumni imported successfully. Temporary passwords were generated for new records.');
        } catch (\Throwable $e) {
            Log::error('Alumni import failed: ' . $e->getMessage());
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function activate(Alumni $alumnus)
    {
        $alumnus->update([
            'status' => 'active',
            'is_active' => true,
            'first_login_at' => $alumnus->first_login_at ?? now(),
        ]);

        return back()->with('success', 'Alumni activated successfully.');
    }

    public function deactivate(Alumni $alumnus)
    {
        $alumnus->update([
            'status' => 'pending',
            'is_active' => false,
        ]);

        return back()->with('success', 'Alumni deactivated successfully.');
    }

    public function suspend(Alumni $alumnus)
    {
        $alumnus->update([
            'status' => 'suspended',
            'is_active' => false,
        ]);

        $alumnus->tokens()->delete();

        return back()->with('success', 'Alumni suspended successfully.');
    }

    public function resendTempPassword(Alumni $alumnus)
    {
        $tempPassword = Str::random(10);

        $alumnus->update([
            'password' => Hash::make($tempPassword),
            'is_active' => false,
            'status' => 'pending',
            'password_changed_at' => null,
        ]);

        try {
            $alumnus->notify(new AlumniTemporaryPasswordNotification($tempPassword));
            $alumnus->update(['temporary_password_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Failed to resend alumni temp password to ' . $alumnus->email . ': ' . $e->getMessage());
            return back()->with('error', 'Password reset but email delivery failed. Check server logs.');
        }

        return back()->with('success', 'New temporary password sent to ' . $alumnus->email);
    }

    public function sendResetLink(Alumni $alumnus)
    {
        $resetUrl = URL::temporarySignedRoute(
            'alumni.reset.show',
            now()->addHours(48),
            ['alumnus' => $alumnus->id]
        );

        try {
            $alumnus->notify(new AlumniPasswordResetNotification($resetUrl));
        } catch (\Throwable $e) {
            Log::error('Failed to send alumni password reset link to ' . $alumnus->email . ': ' . $e->getMessage());
            return back()->with('error', 'Could not send reset link. Check server logs.');
        }

        return back()->with('success', 'Password reset link sent to ' . $alumnus->email . ' (expires in 48 hours).');
    }

    public function destroy(Alumni $alumnus)
    {
        $alumnus->delete();
        return back()->with('success', 'Alumni deleted successfully.');
    }

    private function formData(): array
    {
        return [
            'faculties' => Faculty::orderBy('name')->get(),
            'programs' => Program::orderBy('name')->get(),
            'graduationYears' => GraduationYear::orderByDesc('year')->get(),
            'employmentYears' => EmploymentYear::orderByDesc('year')->get(),
            'employmentStates' => EmploymentState::orderBy('name')->get(),
            'employmentSectors' => EmploymentSector::orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'socialPlatforms' => SocialPlatform::orderBy('name')->get(),
        ];
    }

    private function validateAlumni(Request $request, ?Alumni $alumnus = null): array
    {
        return $request->validate([
            'f_name' => 'required|string|max:255',
            'm_name' => 'nullable|string|max:255',
            'l_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('alumni', 'email')->ignore($alumnus?->id)],
            'password' => [$alumnus ? 'nullable' : 'nullable', 'string', 'min:6', 'confirmed'],
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'phone' => 'nullable|string|max:30',
            'profile_photo' => 'nullable|image|max:2048',
            'nida_number' => ['nullable', 'string', 'max:30', Rule::unique('alumni', 'nida_number')->ignore($alumnus?->id)],
            'settlement_country_id' => 'nullable|exists:countries,id',
            'settlement_region' => 'nullable|string|max:255',
            'settlement_city' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,active,suspended',
            'faculty_id' => 'nullable|exists:faculties,id',
            'program_id' => 'nullable|exists:programs,id',
            'graduation_year_id' => 'nullable|exists:graduation_years,id',
            'degree_program_major' => 'nullable|string|max:255',
            'employment_state_id' => 'nullable|exists:employment_states,id',
            'employment_sector_id' => 'nullable|exists:employment_sectors,id',
            'employment_year_id' => 'nullable|exists:employment_years,id',
            'organization' => 'nullable|string|max:255',
            'social_platform_ids' => 'nullable|array',
            'social_platform_ids.*' => 'exists:social_platforms,id',
        ]);
    }

    private function syncEducationAndEmployment(Alumni $alumnus, array $validated, Request $request): void
    {
        if (!empty($validated['faculty_id']) || !empty($validated['program_id']) || !empty($validated['graduation_year_id']) || !empty($validated['degree_program_major'])) {
            $alumnus->educations()->updateOrCreate(
                ['id' => $request->input('education_id')],
                [
                    'faculty_id' => $validated['faculty_id'] ?? null,
                    'program_id' => $validated['program_id'] ?? null,
                    'graduation_year_id' => $validated['graduation_year_id'] ?? null,
                    'degree_program_major' => $validated['degree_program_major'] ?? null,
                ]
            );
        }

        if (!empty($validated['employment_state_id']) || !empty($validated['employment_sector_id']) || !empty($validated['employment_year_id']) || !empty($validated['organization'])) {
            $alumnus->employments()->updateOrCreate(
                ['id' => $request->input('employment_id')],
                [
                    'employment_state_id' => $validated['employment_state_id'] ?? null,
                    'employment_sector_id' => $validated['employment_sector_id'] ?? null,
                    'employment_year_id' => $validated['employment_year_id'] ?? null,
                    'organization' => $validated['organization'] ?? null,
                    'is_current' => true,
                ]
            );
        }

        $alumnus->socialPlatforms()->sync($validated['social_platform_ids'] ?? []);
    }
}
