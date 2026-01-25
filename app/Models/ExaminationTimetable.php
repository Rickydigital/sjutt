<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExaminationTimetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'faculty_id',
        'course_code',
        'exam_date',
        'start_time',
        'end_time',
        'exam_setup_id',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    /**
     * Get the program this exam belongs to
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the faculty this exam belongs to
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the course for this exam
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_code', 'course_code');
    }

    /**
     * Get the exam setup configuration
     */
    public function examSetup(): BelongsTo
    {
        return $this->belongsTo(ExamSetup::class);
    }

    /**
     * Get venues assigned to this exam (many-to-many)
     */
    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'examination_timetable_venue')
                    ->withPivot('allocated_capacity')
                    ->withTimestamps();
    }

    /**
     * Get course lecturers for this exam
     */
    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'examination_timetable_lecturer')
                    ->withTimestamps();
    }

    /**
     * Get supervisors assigned to this exam
     */
    public function supervisors(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'examination_timetable_supervisor',
            'examination_timetable_id', // pivot FK to this table
            'user_id'                   // pivot FK to users
        )
        ->withPivot('venue_id', 'supervisor_role')
        ->withTimestamps();
    }

    /**
     * Get total expected students from faculty
     */
    public function getExpectedStudentsAttribute(): int
    {
        return $this->faculty->total_students_no ?? 0;
    }

    /**
     * Get total venue capacity
     */
    public function getTotalVenueCapacityAttribute(): int
    {
        return $this->venues->sum('capacity');
    }

    /**
     * Scope to get exams by program
     */
    public function scopeByProgram($query, int $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope to get exams by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('exam_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get upcoming exams
     */
    public function scopeUpcoming($query)
    {
        return $query->where('exam_date', '>=', now());
    }
}