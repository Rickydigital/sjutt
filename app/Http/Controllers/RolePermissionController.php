<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Admin');
    }

    /** ------------------------------------------------------------------
     *  INDEX – list all roles + their permissions
     * ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $roles = Role::with('permissions')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('roles.index', compact('roles'));
    }

    /** ------------------------------------------------------------------
     *  STORE – create a new role (and optionally assign permissions)
     * ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::transaction(function () use ($request) {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web'
            ]);

            // FIXED: Convert IDs to Permission models
            $permissions = Permission::find($request->permissions ?? []);
            $role->syncPermissions($permissions);
        });

        return redirect()->route('roles.index')
            ->with('success', "Role **{$request->name}** created.");
    }

    /** ------------------------------------------------------------------
     *  UPDATE – edit role name & permissions
     * ------------------------------------------------------------------ */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->name]);

            // FIXED: Convert IDs to Permission models
            $permissions = Permission::find($request->permissions ?? []);
            $role->syncPermissions($permissions);
        });

        return redirect()->route('roles.index')
            ->with('success', "Role **{$request->name}** updated.");
    }

    /** ------------------------------------------------------------------
     *  DESTROY – delete a role (only if no users have it)
     * ------------------------------------------------------------------ */
    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->withErrors(['delete' => 'Cannot delete role – users are assigned.']);
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', "Role **{$role->name}** deleted.");
    }

    /** ------------------------------------------------------------------
     *  AJAX – get permissions for a role (used in edit modal)
     * ------------------------------------------------------------------ */
    public function permissions(Role $role)
    {
        return response()->json($role->permissions->pluck('id'));
    }
}