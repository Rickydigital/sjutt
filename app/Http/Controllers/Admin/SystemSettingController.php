<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        $this->authorize('manage-settings');

        $settings = [
            'allow_login_logout' => SystemSetting::get('allow_login_logout', 'true') === 'true'
        ];

        return view('admin.system-settings.index', compact('settings'));
    }

    public function toggleLoginLogout(Request $request)
    {
        $this->authorize('manage-settings');

        $currentValue = SystemSetting::get('allow_login_logout', 'true');
        $newValue = $currentValue === 'true' ? 'false' : 'true';

        SystemSetting::set('allow_login_logout', $newValue);

        return back()->with('success', 'Login/Logout setting updated successfully');
    }

    public function getLoginLogoutStatus()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'allow_login_logout' => SystemSetting::get('allow_login_logout', 'true') === 'true',
                'is_disabled' => SystemSetting::isLoginLogoutDisabled()
            ]
        ]);
    }
}
