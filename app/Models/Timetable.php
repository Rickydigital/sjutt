<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

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

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_code', 'course_code');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(TimetableSemester::class, 'semester_id');
    }

    public function scopeCurrentSemester($query)
    {
        $semesterId = TimetableSemester::first()?->semester_id;
        return $query->where('semester_id', $semesterId);
    }
    public function scopeWeekdays(Builder $query): Builder
    {
        return $query->whereRaw('DAYOFWEEK(DATE_ADD(CURDATE(), INTERVAL 1 DAY)) BETWEEN 2 AND 6'); // Mon-Fri
    }
    }
