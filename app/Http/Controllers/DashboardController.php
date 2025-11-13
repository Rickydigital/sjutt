<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Venue;
use App\Models\Program;
use App\Models\Course;
use App\Models\User;
use App\Models\Timetable;
use App\Models\Faculty;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Fetch counts for dashboard cards
            $buildingCount = Building::count();
            $venueCount = Venue::count();
            $programCount = Program::count();
            $courseCount = Course::count();

            // Fetch lecturers (all users with 'Lecturer' role)
            $lecturers = User::role('Lecturer')->select('id', 'name')->take(10)->get();

            // Initialize timetable data based on user role
            $todaySessions = collect();
            $weeklySessions = collect();
            $programSessions = collect();
            $allSessions = collect();

            $user = Auth::user();
            $today = Carbon::today('Africa/Dar_es_Salaam');

            if ($user->hasRole('Lecturer')) {
                // Lecturer: Today's sessions
                $todaySessions = Timetable::where('lecturer_id', $user->id)
                    ->where('day', $today->format('l'))
                    ->with(['faculty', 'venue', 'lecturer'])
                    ->take(10)
                    ->get()
                    ->map(function ($session) {
                        $session->time_start = date('H:i', strtotime($session->time_start));
                        $session->time_end = date('H:i', strtotime($session->time_end));
                        return $session;
                    });

                // Lecturer: Weekly timetable
                $weeklySessions = Timetable::where('lecturer_id', $user->id)
                    ->with(['faculty', 'venue', 'lecturer'])
                    ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")
                    ->orderBy('time_start')
                    ->take(10)
                    ->get()
                    ->map(function ($session) {
                        $session->time_start = date('H:i', strtotime($session->time_start));
                        $session->time_end = date('H:i', strtotime($session->time_end));
                        return $session;
                    });
            } elseif ($user->hasRole('Administrator')) {
                // Administrator: Sessions for their program
                $programIds = Program::where('administrator_id', $user->id)->pluck('id');
                $programSessions = Timetable::whereHas('faculty', function ($query) use ($programIds) {
                    $query->whereIn('program_id', $programIds);
                })
                    ->with(['faculty', 'venue', 'lecturer'])
                    ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")
                    ->orderBy('time_start')
                    ->take(10)
                    ->get()
                    ->map(function ($session) {
                        $session->time_start = date('H:i', strtotime($session->time_start));
                        $session->time_end = date('H:i', strtotime($session->time_end));
                        return $session;
                    });
            } else {
                // Admin or Timetable Officer: All sessions
                $allSessions = Timetable::with(['faculty', 'venue', 'lecturer'])
                    ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")
                    ->orderBy('time_start')
                    ->take(10)
                    ->get()
                    ->map(function ($session) {
                        $session->time_start = date('H:i', strtotime($session->time_start));
                        $session->time_end = date('H:i', strtotime($session->time_end));
                        return $session;
                    });
            }

            // Log dashboard data for debugging
            Log::info('Dashboard data loaded', [
                'user_id' => $user->id,
                'role' => $user->roles->pluck('name')->first(),
                'today_sessions_count' => $todaySessions->count(),
                'weekly_sessions_count' => $weeklySessions->count(),
                'program_sessions_count' => $programSessions->count(),
                'all_sessions_count' => $allSessions->count(),
                'lecturer_count' => $lecturers->count(),
            ]);

            return view('dashboard', compact(
                'buildingCount',
                'venueCount',
                'programCount',
                'courseCount',
                'lecturers',
                'todaySessions',
                'weeklySessions',
                'programSessions',
                'allSessions'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading dashboard: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => Auth::id()
            ]);
            return back()->withErrors(['error' => 'Unable to load dashboard: ' . $e->getMessage()]);
        }
    }
}