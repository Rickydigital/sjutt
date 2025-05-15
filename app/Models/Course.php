<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use HasFactory;

      protected $fillable = [
        'course_code',
        'name',
        'description',
        'credits'
    ];

    
    public function faculties(): BelongsToMany
    {
        return $this->belongsToMany(Faculty::class, 'course_faculty');
    }

   
    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_lecturer')
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'Lecturer');
                    });
    }
}
