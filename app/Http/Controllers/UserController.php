<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

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

        
            $search = $request->query('search');
            $users = User::when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
            })->paginate(10);
        
            return view('users.index', compact('users'));
        
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $roles = Role::all();
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'nullable|exists:roles,id',
            'phone' => 'nullable|string',
            'gender' => 'nullable|in:Male,Female,Other',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('12345678'),
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
            'status' => 'active',
        ]);

        $roleId = $validated['role'];
        if ($roleId) {
            $role = Role::find($roleId);
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

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(Request $request, User $user)
    {
        if ($logoutResponse = $this->authController->checkStatusAndLogout($request)) {
            return $logoutResponse;
        }

        $roles = Role::all();
        $currentRole = $user->roles->first()->id ?? null;
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|exists:roles,id',
            'phone' => 'nullable|string',
            'gender' => 'nullable|in:Male,Female,Other',
            'password' => 'nullable|string|min:8',
        ]);

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

        $role = Role::find($validated['role']);
        $user->syncRoles([$role]);

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
}