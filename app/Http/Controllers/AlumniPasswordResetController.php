<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class AlumniPasswordResetController extends Controller
{
    public function showForm(Request $request, Alumni $alumnus)
    {
        return view('alumni.reset-password', compact('alumnus'));
    }

    public function resetPassword(Request $request, Alumni $alumnus)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $alumnus->update([
            'password' => Hash::make($request->password),
            'is_active' => true,
            'status' => 'active',
            'password_changed_at' => now(),
            'first_login_at' => $alumnus->first_login_at ?? now(),
        ]);

        // Revoke all existing mobile tokens so they log in fresh with the new password
        $alumnus->tokens()->delete();

        return view('alumni.reset-password-success');
    }
}
