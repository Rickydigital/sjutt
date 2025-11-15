<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UserImport;
use App\Models\TimetableSemester;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $authController;

    public function __construct(AuthenticatedSessionController $authController)
    {
        $this->authController = $authController;
    }

    /**
     * Display a listing of users.
     */
public function index(Request $request)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $search      = $request->query('search');
        $currentUser = Auth::user();

        $query = User::query();

        // -----------------------------------------------------------------
        // 1. Admin → all users
        // 2. Administrator / Dean Of Students → only Lecturers
        // 3. Lecturer → only himself
        // -----------------------------------------------------------------
        if ($currentUser->hasRole('Admin')) {
            // nothing extra – we’ll apply search later
        } elseif ($currentUser->hasAnyRole(['Administrator', 'Dean Of Students'])) {
            $query->role('Lecturer');
        } elseif ($currentUser->hasRole('Lecturer')) {
            $query->where('id', $currentUser->id);
        } else {
            // any other role – show nothing
            $query->where('id', 0);
        }

        // -----------------------------------------------------------------
        // Apply search (name or email) – only if a search term exists
        // -----------------------------------------------------------------
        $query->when($search, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });

        $users = $query->paginate(10);

        return view('users.index', compact('users', 'search'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $currentUser = Auth::user();
        $roles = [];

        if ($currentUser->hasRole('Admin')) {
            $roles = Role::all();
        } elseif ($currentUser->hasAnyRole(['Administrator', 'Dean Of Students'])) {
            $roles = Role::where('name', 'Lecturer')->get();
        }

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $currentUser = Auth::user();
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'gender' => 'nullable|in:Male,Female,Other',
        ];

        if ($currentUser->hasRole('Admin')) {
            $rules['role'] = 'nullable|exists:roles,id';
        }

        $validated = $request->validate($rules);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('12345678'),
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
            'status' => 'active',
        ]);

        if ($currentUser->hasAnyRole(['Administrator', 'Dean Of Students'])) {
            $defaultRole = Role::where('name', 'Lecturer')->firstOrFail();
            $user->assignRole($defaultRole);
        } elseif ($currentUser->hasRole('Admin') && $validated['role']) {
            $role = Role::find($validated['role']);
            $user->assignRole($role);
        } else {
            $defaultRole = Role::where('name', 'Timetable Officer')->firstOrFail();
            $user->assignRole($defaultRole);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $currentUser = Auth::user();
        if ($currentUser->hasRole('Admin') || 
            ($currentUser->hasAnyRole(['Administrator', 'Dean Of Students']) && $user->hasRole('Lecturer'))) {
            return view('users.show', compact('user'));
        }

        return redirect()->route('users.index')->with('error', 'Unauthorized access.');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(Request $request, User $user)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $currentUser = Auth::user();
        $roles = [];
        $currentRole = $user->roles->first()->id ?? null;

        if ($currentUser->hasRole('Admin')) {
            $roles = Role::all();
        } elseif ($currentUser->hasAnyRole(['Administrator', 'Dean Of Students']) && $user->hasRole('Lecturer')) {
            $roles = Role::where('name', 'Lecturer')->get();
        } else {
            return redirect()->route('users.index')->with('error', 'Unauthorized access.');
        }

        return view('users.edit', compact('user', 'roles', 'currentRole'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $currentUser = Auth::user();
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'gender' => 'nullable|in:Male,Female,Other',
            'password' => 'nullable|string|min:8',
        ];

        if ($currentUser->hasRole('Admin')) {
            $rules['role'] = 'required|exists:roles,id';
        }

        $validated = $request->validate($rules);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        if ($currentUser->hasAnyRole(['Administrator', 'Dean Of Students'])) {
            $role = Role::where('name', 'Lecturer')->firstOrFail();
            $user->syncRoles([$role]);
        } elseif ($currentUser->hasRole('Admin')) {
            $role = Role::find($validated['role']);
            $user->syncRoles([$role]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Deactivate the specified user.
     */
    public function deactivate(Request $request, User $user)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $user->update(['status' => 'inactive']);
        return redirect()->route('users.index')->with('success', 'User deactivated successfully.');
    }

    /**
     * Activate the specified user.
     */
    public function activate(Request $request, User $user)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $user->update(['status' => 'active']);
        return redirect()->route('users.index')->with('success', 'User activated successfully.');
    }

    /**
     * Import users from an Excel file.
     */
    public function import(Request $request)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        if (!Auth::user()->hasRole('Admin')) {
            return redirect()->route('users.index')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new UserImport, $request->file('file'));
            return redirect()->route('users.index')->with('success', 'Users imported successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to import users: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to import users: ' . $e->getMessage());
        }
    }

  public function sessionsIndex(Request $request)
    {
        $semesterId = TimetableSemester::getFirstSemester()?->semester_id;

        if (!$semesterId) {
            return view('users.sessions', ['users' => collect(), 'search' => '']);
        }

        $search      = $request->query('search', '');
        $currentUser = Auth::user();

        $query = User::with('roles')
            ->withCount([
                'timetables as total_sessions' => fn($q) => $q->where('semester_id', $semesterId)
            ])
            ->whereHas('roles', fn($q) => $q->where('name', 'Lecturer'));

        // -------------------------------------------------------------
        // Lecturer → show **only** his own row
        // -------------------------------------------------------------
        if ($currentUser->hasRole('Lecturer')) {
            $query->where('id', $currentUser->id);
        }

        $query->when($search, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        })
        ->orderByDesc('total_sessions');

        $users = $query->paginate(15)->withQueryString();

        return view('users.sessions', compact('users', 'semesterId', 'search'));
    }

    /* ---------------------------------------------------------------
       sessionsShow() and sessionsPdf() – unchanged (they already
       receive a $user model, so the restriction is handled by the
       route / middleware if you want to keep it safe).
       --------------------------------------------------------------- */

    public function sessionsShow(User $user)
    {
        $semesterId = TimetableSemester::getFirstSemester()?->semester_id;

        $slots = DB::table('timetables')
            ->where('lecturer_id', $user->id)
            ->where('semester_id', $semesterId)
            ->leftJoin('faculties', 'timetables.faculty_id', '=', 'faculties.id')
            ->leftJoin('venues', 'timetables.venue_id', '=', 'venues.id')
            ->select(
                'timetables.day',
                DB::raw('TIME_FORMAT(timetables.time_start, "%H:%i") as start'),
                DB::raw('TIME_FORMAT(timetables.time_end, "%H:%i") as end'),
                'timetables.course_code',
                'timetables.activity',
                'timetables.group_selection',
                'faculties.name as faculty_name',
                'venues.name as venue_name',
                'venues.longform as venue_longform'
            )
            ->orderBy('day')
            ->orderBy('time_start')
            ->get()
            ->groupBy(fn($i) => "{$i->day}|{$i->start}|{$i->end}")
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'day'       => $first->day,
                    'start'     => $first->start,
                    'end'       => $first->end,
                    'courses'   => $group->pluck('course_code')->unique()->values()->toArray(),
                    'groups'    => $group->map(fn($i) => $i->group_selection === 'All Groups' ? 'All Groups' : $i->group_selection)
                                     ->unique()
                                     ->implode(', '),
                    'activity'  => $group->pluck('activity')->filter()->unique()->implode(' / '),
                    'count'     => $group->count(),
                    'faculty'   => $group->pluck('faculty_name')->filter()->unique()->implode(' / '),
                    'venue'     => $group->pluck('venue_longform')->filter()->unique()->implode(' / '),
                    'venue_code'=> $group->pluck('venue_name')->filter()->unique()->implode(' / '),
                ];
            })
            ->values();

        $user->load(['roles']);

        return view('users.sessions-show', compact('user', 'slots', 'semesterId'));
    }

    public function sessionsPdf(User $user)
    {
        $semester = TimetableSemester::getFirstSemester();

        $slots = DB::table('timetables')
            ->where('lecturer_id', $user->id)
            ->where('semester_id', $semester->semester_id)
            ->leftJoin('faculties', 'timetables.faculty_id', '=', 'faculties.id')
            ->leftJoin('venues', 'timetables.venue_id', '=', 'venues.id')
            ->select(
                'timetables.day',
                DB::raw('TIME_FORMAT(timetables.time_start, "%H:%i") as start'),
                DB::raw('TIME_FORMAT(timetables.time_end, "%H:%i") as end'),
                'timetables.course_code',
                'timetables.activity',
                'timetables.group_selection',
                'faculties.name as faculty_name',
                'venues.name as venue_name',
                'venues.longform as venue_longform'
            )
            ->orderBy('day')
            ->orderBy('time_start')
            ->get()
            ->groupBy(fn($i) => "{$i->day}|{$i->start}|{$i->end}")
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'day'       => $first->day,
                    'start'     => $first->start,
                    'end'       => $first->end,
                    'courses'   => $group->pluck('course_code')->unique()->values()->toArray(),
                    'groups'    => $group->map(fn($i) => $i->group_selection === 'All Groups' ? 'All Groups' : $i->group_selection)
                                     ->unique()
                                     ->implode(', '),
                    'activity'  => $group->pluck('activity')->filter()->unique()->implode(' / '),
                    'faculty'   => $group->pluck('faculty_name')->filter()->unique()->implode(' / '),
                    'venue'     => $group->pluck('venue_longform')->filter()->unique()->implode(' / '),
                    'venue_code'=> $group->pluck('venue_name')->filter()->unique()->implode(' / '),
                ];
            })
            ->values();

        $user->load(['roles']);

        $pdf = Pdf::loadView('users.pdf.sessions', compact('user', 'slots', 'semester'))
                  ->setPaper('a4', 'portrait')
                  ->setOptions([
                      'isRemoteEnabled' => true,
                      'defaultFont'     => 'DejaVu Sans',
                      'dpi'             => 150,
                      'isHtml5ParserEnabled' => true,
                      'margin_top'      => 0,
                      'margin_right'    => 0,
                      'margin_bottom'   => 0,
                      'margin_left'     => 0,
                  ]);

        $filename = "lecturer-schedule-{$user->name}-" . now()->format('Y-m-d') . ".pdf";
        return $pdf->download($filename);
    }
}