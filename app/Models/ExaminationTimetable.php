<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationTimetable extends Model {
    use HasFactory;

    protected $fillable = [
        'timetable_type', 'program', 'semester', 'course_code',
        'faculty_id', 'year_id', 'exam_date',
        'start_time', 'end_time', 'venue_id'
    ];

    protected $dates = ['exam_date'];

    public function faculty() {
        return $this->belongsTo(Faculty::class);
    }

    public function year() {
        return $this->belongsTo(Year::class);
    }

    public function venue() {
        return $this->belongsTo(Venue::class);
    }
}
