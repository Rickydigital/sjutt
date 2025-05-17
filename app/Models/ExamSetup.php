<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'start_date',
        'end_date',
        'include_weekends',
        'time_slots',
        'programs',
        'academic_year',
        'semester',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'include_weekends' => 'boolean',
        'time_slots' => 'array',
        'programs' => 'array',
        'type' => 'array',
    ];
}