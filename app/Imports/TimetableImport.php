<?php

namespace App\Imports;

use App\Models\Timetable;
use App\Models\Faculty;
use App\Models\Venue;
use App\Models\Course;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TimetableImport implements ToModel, WithHeadingRow, WithValidation
{
    public $errors = [];

    public function model(array $row)
    {
        try {
            // Get current row number (available via Laravel Excel)
            $rowNumber = $this->rowNumber();

            // Fetch related models
            $faculty = Faculty::where('name', $row['faculty'])->first();
            $venue = Venue::where('name', $row['venue'])->first();
            $course = Course::where('course_code', $row['course_code'])->first();
            $lecturer = User::where('name', $row['lecturer'])->where('role', 'lecturer')->first();

            // Validate existence of required models
            if (!$faculty) {
                $this->errors[] = "Row {$rowNumber}: Faculty '{$row['faculty']}' not found.";
                return null;
            }
            if (!$venue) {
                $this->errors[] = "Row {$rowNumber}: Venue '{$row['venue']}' not found.";
                return null;
            }
            if (!$course) {
                $this->errors[] = "Row {$rowNumber}: Course '{$row['course_code']}' not found.";
                return null;
            }
            if (!$lecturer) {
                $this->errors[] = "Row {$rowNumber}: Lecturer '{$row['lecturer']}' not found or not a lecturer.";
                return null;
            }

            // Validate group selection
            $groups = explode(',', $row['group_selection']);
            $validGroups = \App\Models\FacultyGroup::where('faculty_id', $faculty->id)
                ->whereIn('group_name', $groups)
                ->pluck('group_name')
                ->toArray();
            if (count($groups) !== count($validGroups) && $row['group_selection'] !== 'All Groups') {
                $this->errors[] = "Row {$rowNumber}: Invalid groups '{$row['group_selection']}' for faculty {$faculty->name}.";
                return null;
            }

            // Validate venue capacity
            $studentCount = $this->getStudentCount($faculty, $row['group_selection']);
            if ($venue->capacity < $studentCount) {
                $this->errors[] = "Row {$rowNumber}: Venue capacity ({$venue->capacity}) insufficient for {$studentCount} students.";
                return null;
            }

            // Prepare data for conflict checks
            $data = [
                'day' => $row['day'],
                'faculty_id' => $faculty->id,
                'time_start' => $row['time_start'],
                'time_end' => $row['time_end'],
                'course_code' => $row['course_code'],
                'venue_id' => $venue->id,
                'lecturer_id' => $lecturer->id,
                'group_selection' => $row['group_selection'],
            ];

            // Perform conflict checks
            $conflicts = $this->checkConflicts($data);
            if (!empty($conflicts)) {
                foreach ($conflicts as $conflict) {
                    $this->errors[] = "Row {$rowNumber}: {$conflict}";
                }
                return null;
            }

            // Create timetable entry if no errors
            return new Timetable([
                'day' => $row['day'],
                'faculty_id' => $faculty->id,
                'time_start' => $row['time_start'],
                'time_end' => $row['time_end'],
                'course_code' => $row['course_code'],
                'course_name' => $course->name,
                'activity' => $row['activity'],
                'venue_id' => $venue->id,
                'lecturer_id' => $lecturer->id,
                'group_selection' => $row['group_selection'],
            ]);
        } catch (\Exception $e) {
            Log::error("Import error in row {$rowNumber}: " . $e->getMessage(), $row);
            $this->errors[] = "Row {$rowNumber}: Error processing - {$e->getMessage()}";
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'faculty' => 'required|string',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
            'course_code' => 'required|string',
            'activity' => 'required|string|max:255',
            'venue' => 'required|string',
            'lecturer' => 'required|string',
            'group_selection' => 'required|string',
        ];
    }

    private function getStudentCount(Faculty $faculty, string $groupSelection): int
    {
        if ($groupSelection === 'All Groups') {
            return $faculty->total_students_no ?? \App\Models\FacultyGroup::where('faculty_id', $faculty->id)->sum('student_count');
        }
        $groups = explode(',', $groupSelection);
        return \App\Models\FacultyGroup::where('faculty_id', $faculty->id)
            ->whereIn('group_name', $groups)
            ->sum('student_count');
    }

    private function checkConflicts(array $data): array
    {
        $conflicts = [];

        // 1. Lecturer Conflict
        if ($data['lecturer_id']) {
            $lecturerConflict = Timetable::where('day', $data['day'])
                ->where('lecturer_id', $data['lecturer_id'])
                ->where(function ($query) use ($data) {
                    $query->where(function ($q) use ($data) {
                        $q->where('time_start', '<', $data['time_end'])
                          ->where('time_end', '>', $data['time_start']);
                    });
                })
                ->first();
            if ($lecturerConflict) {
                $conflicts[] = "Lecturer is assigned to {$lecturerConflict->course_code} for {$lecturerConflict->faculty->name} from {$lecturerConflict->time_start} to {$lecturerConflict->time_end}.";
            }
        }

        // 2. Venue Conflict
        $venueConflict = Timetable::where('day', $data['day'])
            ->where('venue_id', $data['venue_id'])
            ->where(function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    $q->where('time_start', '<', $data['time_end'])
                      ->where('time_end', '>', $data['time_start']);
                });
            })
            ->first();
        if ($venueConflict) {
            $conflicts[] = "Venue {$venueConflict->venue->name} is in use by {$venueConflict->faculty->name} for {$venueConflict->course_code} from {$venueConflict->time_start} to {$venueConflict->time_end}.";
        }

        // 3. Group Conflict
        $groups = $data['group_selection'] === 'All Groups' 
            ? \App\Models\FacultyGroup::where('faculty_id', $data['faculty_id'])->pluck('group_name')->toArray()
            : explode(',', $data['group_selection']);
        foreach ($groups as $group) {
            $groupConflict = Timetable::where('day', $data['day'])
                ->where('faculty_id', $data['faculty_id'])
                ->where('group_selection', 'like', "%{$group}%")
                ->where(function ($query) use ($data) {
                    $query->where(function ($q) use ($data) {
                        $q->where('time_start', '<', $data['time_end'])
                          ->where('time_end', '>', $data['time_start']);
                    });
                })
                ->first();
            if ($groupConflict) {
                $conflicts[] = "Group {$group} has a session for {$groupConflict->course_code} from {$groupConflict->time_start} to {$groupConflict->time_end}.";
            }
        }

        // 4. Faculty "All Groups" Conflict
        if ($data['group_selection'] === 'All Groups') {
            $facultyConflict = Timetable::where('day', $data['day'])
                ->where('faculty_id', $data['faculty_id'])
                ->where(function ($query) use ($data) {
                    $query->where(function ($q) use ($data) {
                        $q->where('time_start', '<', $data['time_end'])
                          ->where('time_end', '>', $data['time_start']);
                    });
                })
                ->first();
            if ($facultyConflict) {
                $conflicts[] = "Faculty has a session for groups ({$facultyConflict->group_selection}) with {$facultyConflict->course_code} from {$facultyConflict->time_start} to {$facultyConflict->time_end}.";
            }
        } else {
            $allGroupsConflict = Timetable::where('day', $data['day'])
                ->where('faculty_id', $data['faculty_id'])
                ->where('group_selection', 'All Groups')
                ->where(function ($query) use ($data) {
                    $query->where(function ($q) use ($data) {
                        $q->where('time_start', '<', $data['time_end'])
                          ->where('time_end', '>', $data['time_start']);
                    });
                })
                ->first();
            if ($allGroupsConflict) {
                $conflicts[] = "Faculty has a session for all groups with {$allGroupsConflict->course_code} from {$allGroupsConflict->time_start} to {$allGroupsConflict->time_end}.";
            }
        }

        // 5. Course Conflict
        $courseConflict = Timetable::where('day', $data['day'])
            ->where('course_code', $data['course_code'])
            ->where('faculty_id', $data['faculty_id'])
            ->where(function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    $q->where('time_start', '<', $data['time_end'])
                      ->where('time_end', '>', $data['time_start']);
                });
            })
            ->first();
        if ($courseConflict) {
            $conflicts[] = "Course {$data['course_code']} is already scheduled for {$courseConflict->faculty->name} from {$courseConflict->time_start} to {$courseConflict->time_end}.";
        }

        return $conflicts;
    }

    // Helper to get current row number
    private function rowNumber(): int
    {
        return $this->getRowIndex() + 1; // +1 to account for heading row
    }

    // Required for WithValidation
    public function customValidationMessages()
    {
        return [
            'day.required' => 'The day field is required.',
            'day.in' => 'The day must be one of: Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday.',
            'faculty.required' => 'The faculty field is required.',
            'time_start.required' => 'The start time field is required.',
            'time_start.date_format' => 'The start time must be in H:i format (e.g., 08:00).',
            'time_end.required' => 'The end time field is required.',
            'time_end.date_format' => 'The end time must be in H:i format (e.g., 09:00).',
            'time_end.after' => 'The end time must be after the start time.',
            'course_code.required' => 'The course code field is required.',
            'activity.required' => 'The activity field is required.',
            'venue.required' => 'The venue field is required.',
            'lecturer.required' => 'The lecturer field is required.',
            'group_selection.required' => 'The group selection field is required.',
        ];
    }
}