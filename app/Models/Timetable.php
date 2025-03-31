<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty', 'year', 'day', 'time_start', 'time_end', 
        'course_code', 'activity', 'venue'
    ];
}
