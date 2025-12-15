<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    /**
     * Get the latest published app version for a specific platform.
     */
    public function latest(Request $request)
    {
        $platform = $request->query('platform', 'android');

        $latestVersion = AppVersion::where('platform', $platform)
            ->orderBy('version_code', 'desc')
            ->first();

        if (!$latestVersion) {
            return response()->json(['message' => 'No version found for this platform.'], 404);
        }

        return response()->json($latestVersion);
    }
}