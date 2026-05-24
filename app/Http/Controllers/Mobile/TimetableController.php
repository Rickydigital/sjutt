<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ExaminationTimetable;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\TimetableSemester;
use App\Models\User;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    /**
     * Get the current active timetable semester
     */
    private function getCurrentTimetableSemester()
    {
        return TimetableSemester::with('semester')
            ->active()
            ->latest('activated_at')
            ->latest('id')
            ->first() ?? TimetableSemester::with('semester')
            ->latest('id')
            ->first();
    }

    /**
     * Student Lecture Timetable (by Faculty)
     */
    public function getLectureTimetables(Request $request)
    {
        $facultyId = $request->query('faculty_id');

        if (!$facultyId) {
            return response()->json([
                'success' => false,
                'error'   => 'Missing faculty_id'
            ], 400);
        }

        $timetableSemester = $this->getCurrentTimetableSemester();

        if (!$timetableSemester) {
            return response()->json([
                'success' => false,
                'error'   => 'No timetable semester configured.'
            ], 422);
        }

        $timetables = Timetable::with(['lecturer', 'venue', 'course'])
            ->where('faculty_id', $facultyId)
            ->where('semester_id', $timetableSemester->id)
            ->whereIn('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")
            ->orderBy('time_start')
            ->get();

        $grouped = $timetables->groupBy('day')->map(fn($entries) => $entries->values());

        // Ensure all days exist
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($days as $day) {
            if (!isset($grouped[$day])) {
                $grouped[$day] = collect([]);
            }
        }

        return response()->json([
            'success'       => true,
            'semester'      => $timetableSemester->semester?->name ?? 'N/A',
            'academic_year' => $timetableSemester->academic_year,
            'data'          => $grouped
        ], 200);
    }

    /**
     * Lecturer Timetable
     */
    public function getLecturerTimetables(Request $request)
    {
        $lecturerId = $request->query('lecturer_id');

        if (!$lecturerId) {
            return response()->json([
                'success' => false,
                'error'   => 'Missing lecturer_id'
            ], 400);
        }

        $lecturer = User::find($lecturerId);
        if (!$lecturer) {
            return response()->json([
                'success' => false,
                'error'   => 'Lecturer not found'
            ], 404);
        }

        $timetableSemester = $this->getCurrentTimetableSemester();

        if (!$timetableSemester) {
            return response()->json([
                'success' => false,
                'error'   => 'No timetable semester configured.'
            ], 422);
        }

        $timetables = Timetable::with(['faculty', 'venue', 'course'])
            ->where('lecturer_id', $lecturerId)
            ->where('semester_id', $timetableSemester->id)
            ->whereIn('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")
            ->orderBy('time_start')
            ->get();

        $groupedByDay = $timetables->groupBy('day');

        $finalData = collect();

        foreach ($groupedByDay as $day => $dayEntries) {
            $slots = $dayEntries->groupBy(function ($item) {
                return $item->time_start . '|' . $item->time_end . '|' . $item->venue_id . '|' . $item->course_code;
            })->map(function ($group) {
                $first = $group->first();
                $faculties = $group->pluck('faculty.name')->filter()->unique()->values();

                return [
                    'course_code'     => $first->course_code,
                    'course_name'     => $first->course?->name ?? '—',
                    'activity'        => $first->activity,
                    'venue'           => $first->venue?->name ?? '—',
                    'time_start'      => substr($first->time_start, 0, 5),
                    'time_end'        => substr($first->time_end, 0, 5),
                    'faculties'       => $faculties->implode(' / '),
                    'faculty_count'   => $faculties->count(),
                    'group_selection' => $first->group_selection ?? 'All Groups',
                ];
            })->values();

            $finalData[$day] = $slots;
        }

        // Ensure all days exist
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($days as $day) {
            if (!isset($finalData[$day])) {
                $finalData[$day] = [];
            }
        }

        return response()->json([
            'success'       => true,
            'lecturer_id'   => (int) $lecturerId,
            'lecturer_name' => $lecturer->name,
            'semester'      => $timetableSemester->semester?->name ?? 'N/A',
            'academic_year' => $timetableSemester->academic_year,
            'data'          => $finalData
        ], 200);
    }

    /**
     * Venue Timetable
     */
    public function getVenueTimetables(Request $request)
    {
        $venueId = $request->query('venue_id');

        if (!$venueId) {
            return response()->json([
                'success' => false,
                'error'   => 'Missing venue_id'
            ], 400);
        }

        $timetableSemester = $this->getCurrentTimetableSemester();

        if (!$timetableSemester) {
            return response()->json([
                'success' => false,
                'error'   => 'No timetable semester configured.'
            ], 422);
        }

        $timetables = Timetable::with(['lecturer', 'faculty', 'course'])
            ->where('semester_id', $timetableSemester->id)
            ->where(function ($q) use ($venueId) {
                $q->where('venue_id', $venueId)
                  ->orWhere('venue_id', 'like', $venueId . ',%')
                  ->orWhere('venue_id', 'like', '%,' . $venueId . ',%')
                  ->orWhere('venue_id', 'like', '%,' . $venueId);
            })
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")
            ->orderBy('time_start')
            ->get();

        $grouped = $timetables->groupBy('day')->map(function ($entries) {
            return $entries->map(fn($entry) => [
                'course_code' => $entry->course_code,
                'activity'    => $entry->activity,
                'faculty'     => $entry->faculty?->name ?? '—',
                'lecturer'    => $entry->lecturer?->name ?? '—',
                'time_start'  => substr($entry->time_start, 0, 5),
                'time_end'    => substr($entry->time_end, 0, 5),
            ]);
        });

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($days as $day) {
            if (!isset($grouped[$day])) {
                $grouped[$day] = collect([]);
            }
        }

        return response()->json([
            'success'       => true,
            'venue_id'      => (int) $venueId,
            'semester'      => $timetableSemester->semester?->name ?? 'N/A',
            'academic_year' => $timetableSemester->academic_year,
            'data'          => $grouped
        ], 200);
    }

    /**
     * Course Students (Per Current Semester)
     */
    public function getCourseStudents(Request $request)
    {
        $courseCode = $request->query('course_code');

        if (!$courseCode) {
            return response()->json([
                'success' => false,
                'error'   => 'Missing course_code parameter'
            ], 400);
        }

        $timetableSemester = $this->getCurrentTimetableSemester();

        $course = Course::with('faculties')
            ->where('course_code', $courseCode)
            ->where('semester_id', $timetableSemester?->semester_id)
            ->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'error'   => 'Course not found in current semester'
            ], 404);
        }

        $facultyIds = $course->faculties->pluck('id');

        $students = Student::with(['faculty', 'program'])
            ->whereIn('faculty_id', $facultyIds)
            ->select('id', 'first_name', 'last_name', 'reg_no', 'faculty_id', 'program_id', 'gender', 'phone')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($student) => [
                'id'         => $student->id,
                'full_name'  => trim("{$student->first_name} {$student->last_name}"),
                'reg_no'     => $student->reg_no,
                'faculty'    => $student->faculty?->name ?? '—',
                'program'    => $student->program?->name ?? '—',
                'gender'     => $student->gender,
                'phone'      => $student->phone,
            ]);

        return response()->json([
            'success'         => true,
            'course_code'     => $course->course_code,
            'course_name'     => $course->name ?? '—',
            'semester'        => $timetableSemester?->semester?->name,
            'total_students'  => $students->count(),
            'students'        => $students
        ], 200);
    }

    /**
     * Examination Timetables
     */
    public function getExaminationTimetables(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $yearId = $request->query('year_id');

        if (!$facultyId || !$yearId) {
            return response()->json(['error' => 'Missing faculty_id or year_id'], 400);
        }

        $timetables = ExaminationTimetable::where('faculty_id', $facultyId)
            ->where('year_id', $yearId)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $timetables
        ], 200);
    }
}