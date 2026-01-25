<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get exam setups for this semester
     */
    public function examSetups(): HasMany
    {
        return $this->hasMany(ExamSetup::class);
    }

    /**
     * Get timetable semesters
     */
    public function timetableSemesters(): HasMany
    {
        return $this->hasMany(TimetableSemester::class);
    }
}