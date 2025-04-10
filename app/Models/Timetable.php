<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
         'faculty_id', 'year_id', 'day', 'time_start', 'time_end', 
        'course_code', 'activity', 'venue_id'
    ];

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
