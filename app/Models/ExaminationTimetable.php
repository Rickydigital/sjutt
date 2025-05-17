<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExaminationTimetable extends Model
{
    protected $fillable = [
        'faculty_id',
        'course_code',
        'exam_date',
        'start_time',
        'end_time',
        'venue_id',
        'group_selection',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function lecturers()
    {
        return $this->belongsToMany(User::class, 'examination_timetable_lecturer');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_code', 'course_code');
    }
}