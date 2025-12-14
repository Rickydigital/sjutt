<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LecturerCourseController extends Controller
{
    /**
     * Get courses taught by a specific lecturer in the current semester.
     */
    public function index(Request $request)
    {
        $request->validate([
            'lecturer_id' => 'required|integer|exists:users,id',
        ]);

        $lecturerId = $request->query('lecturer_id');

        

        $courses = Course::with([
                'lecturers' => function ($query) {
                    $query->with(['roles' => function ($q) {
                        $q->select('roles.id', 'roles.name', 'roles.guard_name');
                    }])
                    ->select([
                        'users.id', 'users.name', 'users.phone', 'users.gender',
                        'users.email', 'users.email_verified_at', 'users.status',
                        'users.fcm_token'
                    ]);
                },
                'faculties' => function ($query) {
                    $query->select([
                        'faculties.id', 'faculties.name', 'faculties.total_students_no',
                        'faculties.description', 'faculties.program_id'
                    ]);
                },
                'semester'
            ])
            ->whereHas('lecturers', function ($query) use ($lecturerId) {
                $query->where('users.id', $lecturerId);
            })
            
            // ->whereHas('semester', function ($q) {
            //     $q->where('is_current', true); // adjust if your column name differs
            // })
            ->select([
                'id', 'name as course_name', 'course_code', 'description', 'credits'
            ])
            ->get();

        // Transform to match exact response structure
        $formattedCourses = $courses->map(function ($course) {
            return [
                'id'           => $course->id,
                'course_name'  => $course->course_name,
                'course_code'  => $course->course_code,
                'description'  => $course->description,
                'credits'      => $course->credits,
                'lecturers'    => $course->lecturers->map(function ($lecturer) {
                    return [
                        'id'                 => $lecturer->id,
                        'name'               => $lecturer->name,
                        'phone'              => $lecturer->phone,
                        'gender'             => $lecturer->gender,
                        'email'              => $lecturer->email,
                        'email_verified_at'  => $lecturer->email_verified_at,
                        'status'             => $lecturer->status,
                        'roles'              => $lecturer->roles->map(function ($role) {
                            return [
                                'id'         => $role->id,
                                'name'       => $role->name,
                                'guard_name' => $role->guard_name,
                            ];
                        })->toArray(),
                        'fcm_token'          => $lecturer->fcm_token,
                    ];
                })->toArray(),
                'faculties' => $course->faculties->map(function ($faculty) {
                    return [
                        'id'                => $faculty->id,
                        'name'              => $faculty->name,
                        'total_students_no' => $faculty->total_students_no,
                        'description'       => $faculty->description,
                        'program_id'        => $faculty->program_id,
                    ];
                })->toArray(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $formattedCourses,
            'message' => 'Courses fetched successfully.'
        ]);
    }
}