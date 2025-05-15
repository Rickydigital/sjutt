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
        'program_id'
    ];

    public function students() {
        return $this->hasMany(Student::class);
    }

    public function timetables() {
        return $this->hasMany(Timetable::class);
    }

    public function examinationTimetables() {
        return $this->hasMany(ExaminationTimetable::class);
    }

     public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * A faculty has many courses.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_faculty');
    }

    /**
     * A faculty has many groups.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(FacultyGroup::class);
    }
}
