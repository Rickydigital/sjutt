<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'total_students_no',
        'description',
        'program_id',
    ];

    protected $casts = [
        'total_students_no' => 'integer',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function examinationTimetables(): HasMany
    {
        return $this->hasMany(ExaminationTimetable::class);
    }

    public function electionPositions(): BelongsToMany
    {
        return $this->belongsToMany(
            ElectionPosition::class,
            'election_position_faculty',
            'faculty_id',
            'election_position_id'
        )->withTimestamps();
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_faculty');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(FacultyGroup::class);
    }
}