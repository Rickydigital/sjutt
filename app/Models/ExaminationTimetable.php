<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationTimetable extends Model {
    use HasFactory;

    protected $fillable = [
        'timetable_type', 'program', 'semester', 'course_code',
        'faculty', 'year', 'exam_date',
        'start_time', 'end_time', 'venue'
    ];
}
