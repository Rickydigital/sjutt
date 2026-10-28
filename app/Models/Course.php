<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_code',
        'name',
        'description',
        'credits',
        'hours',
        'practical_hrs',
        'session',
        'semester_id',
        'cross_catering',
        'is_workshop',
    ];

    protected $casts = [
        'cross_catering' => 'boolean',
        'is_workshop' => 'boolean',
        'practical_hrs' => 'integer',
        'hours' => 'integer',
        'session' => 'integer',
    ];

    public function faculties(): BelongsToMany
{
    return $this->belongsToMany(Faculty::class, 'course_faculty')
        ->withPivot('student_count')
        ->withTimestamps();
}

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class, 'course_code', 'course_code');
    }

    public function examinationTimetables(): HasMany
    {
        return $this->hasMany(ExaminationTimetable::class, 'course_code', 'course_code');
    }

    public function lecturers(): BelongsToMany
{
    return $this->belongsToMany(
        User::class,
        'course_lecturer',
        'course_id',
        'user_id'
    )
        ->wherePivot('is_active', true)
        ->withPivot(['is_active', 'assigned_at', 'removed_at'])
        ->withTimestamps()
        ->select('users.id', 'users.name');
}

    /**
     * All current and previous lecturer assignments for audit/history views.
     */
    public function lecturerHistory(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'course_lecturer',
            'course_id',
            'user_id'
        )
            ->withPivot(['is_active', 'assigned_at', 'removed_at'])
            ->withTimestamps();
    }

    /**
     * Activate selected lecturers and retain removed lecturers as history.
     */
    public function syncLecturerAssignments(array $lecturerIds): void
    {
        $selectedIds = collect($lecturerIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $existing = $this->lecturerHistory()
            ->get()
            ->keyBy(fn (User $user) => (int) $user->id);

        foreach ($selectedIds as $lecturerId) {
            $assignment = $existing->get($lecturerId);

            if ($assignment) {
                $values = [
                    'is_active' => true,
                    'removed_at' => null,
                ];

                if (!(bool) $assignment->pivot->is_active) {
                    $values['assigned_at'] = now();
                }

                $this->lecturerHistory()->updateExistingPivot($lecturerId, $values);
                continue;
            }

            $this->lecturerHistory()->attach($lecturerId, [
                'is_active' => true,
                'assigned_at' => now(),
                'removed_at' => null,
            ]);
        }

        $activeIdsToRemove = $existing
            ->filter(fn (User $user) => (bool) $user->pivot->is_active)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->diff($selectedIds);

        foreach ($activeIdsToRemove as $lecturerId) {
            $this->lecturerHistory()->updateExistingPivot($lecturerId, [
                'is_active' => false,
                'removed_at' => now(),
            ]);
        }
}

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
