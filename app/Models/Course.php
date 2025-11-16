<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_code',
        'name',
        'description',
        'credits',
        'hours',
        'practical_hrs',
        'session',
        'semester_id', 
        'cross_catering',
        'is_workshop'
    ];

    protected $casts = [
        'cross_catering' => 'boolean',
        'is_workshop' => 'boolean',
        'practical_hrs' => 'integer',
    ];

    public function faculties(): BelongsToMany
    {
        return $this->belongsToMany(Faculty::class, 'course_faculty');
    }

    public function timetables(): HasMany
{
    return $this->hasMany(Timetable::class, 'course_code', 'course_code');
}

    public function lecturers(): BelongsToMany
{
    return $this->belongsToMany(
        User::class,
        'course_lecturer',
        'course_id',  
        'user_id'     
    )
    ->whereHas('roles', function ($query) {
        $query->where('name', 'Lecturer');
    })
    ->select('users.id', 'users.name');
}

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}