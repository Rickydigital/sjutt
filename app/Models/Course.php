<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_faculty', 
        'academic_programme', 
        'entry_qualifications', 
        'tuition_fee_per_year', 
        'duration'
    ];
}
