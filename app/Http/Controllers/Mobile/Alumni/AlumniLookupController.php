<?php

namespace App\Http\Controllers\Mobile\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\EmploymentSector;
use App\Models\EmploymentState;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\SocialPlatform;

class AlumniLookupController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'faculties' => Faculty::select('id', 'name')->orderBy('name')->get(),
                'programs' => Program::select('id', 'name', 'short_name')->orderBy('name')->get(),
                'employment_states' => EmploymentState::select('id', 'name')->orderBy('name')->get(),
                'employment_sectors' => EmploymentSector::select('id', 'name')->orderBy('name')->get(),
                'countries' => Country::select('id', 'name')->orderBy('name')->get(),
                'social_platforms' => SocialPlatform::select('id', 'name')->orderBy('name')->get(),
            ],
        ]);
    }
}
