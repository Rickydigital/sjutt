<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

class ExamSetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_id',
        'start_date',
        'end_date',
        'include_weekends',
        'time_slots',
        'academic_year',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'include_weekends' => 'boolean',
        'time_slots' => 'array',
    ];

    /**
     * Get the semester for this exam setup
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Get all examination timetables for this setup
     */
    public function examinationTimetables(): HasMany
    {
        return $this->hasMany(ExaminationTimetable::class);
    }

    /**
     * Get the active exam setup
     */
    public static function getActive(): ?self
    {
        return self::query()
                   ->where('end_date', '>=', now())
                   ->where('start_date', '<=', now())
                   ->first();
    }

    /**
     * Get exam setups for a specific semester
     */
    public static function getBySemester(int $semesterId): Collection
    {
        return self::query()->where('semester_id', $semesterId)->get();
    }

    /**
     * Scope to get active exam setups
     */
    public function scopeActive($query)
    {
        return $query->where('end_date', '>=', now())
                     ->where('start_date', '<=', now());
    }

    /**
     * Check if weekends are included
     */
    public function includesWeekends(): bool
    {
        return $this->include_weekends;
    }

    /**
     * Get formatted time slots
     */
    public function getFormattedTimeSlotsAttribute(): array
    {
        return $this->time_slots ?? [];
    }
}