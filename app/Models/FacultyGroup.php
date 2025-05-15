<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyGroup extends Model
{
    protected $fillable = [
        'faculty_id', 
        'group_name', 
        'student_count'
    ];

    
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }
}
