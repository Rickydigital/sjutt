<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'day',
        'time_start',
        'time_end',
        'course_code',
        'activity',
        'venue_id',
        'lecturer_id',
        'group_selection',
        'semester_id',
    ];

    protected $casts = [
        'faculty_id' => 'integer',
        'lecturer_id' => 'integer',
        'semester_id' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_code', 'course_code');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * NOTE:
     * venue_id may store comma-separated venue ids.
     * This relation only works reliably when a single venue id is stored.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * timetable.semester_id stores timetable_semesters.id
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(TimetableSemester::class, 'semester_id');
    }

    /**
     * Optional helper if you want clearer naming in controllers/views.
     */
    public function timetableSemester(): BelongsTo
    {
        return $this->belongsTo(TimetableSemester::class, 'semester_id');
    }

    /**
     * Get only records that belong to the currently active timetable setup.
     */
    public function scopeCurrentSemester(Builder $query): Builder
    {
        $setup = method_exists(TimetableSemester::class, 'getCurrent')
            ? TimetableSemester::getCurrent()
            : TimetableSemester::orderByDesc('activated_at')->orderByDesc('id')->first();

        return $setup
            ? $query->where('semester_id', (int) $setup->id)
            : $query->whereRaw('1 = 0');
    }

    public function scopeForDay(Builder $query, string $day): Builder
    {
        return $query->where('day', $day);
    }

    /**
     * Helper scope for a specific timetable setup id.
     */
    public function scopeForSetup(Builder $query, int $setupId): Builder
    {
        return $query->where('semester_id', $setupId);
    }

    /**
     * Returns venue ids as integer array from comma-separated venue_id column.
     */
    public function getVenueIdsAttribute(): array
    {
        if (!$this->venue_id) {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            array_filter(array_map('trim', explode(',', (string) $this->venue_id)))
        )));
    }

    /**
     * Optional helper to get the real semester record through timetable setup.
     */
    public function getActualSemesterAttribute(): ?Semester
    {
        return $this->semester?->semester;
    }
}