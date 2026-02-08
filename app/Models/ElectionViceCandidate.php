<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectionViceCandidate extends Model
{
    protected $fillable = [
        'election_candidate_id',
        'student_id',
        'faculty_id',
        'program_id',
        'photo',
        'description',
    ];

    public function candidate()
    {
        return $this->belongsTo(ElectionCandidate::class, 'election_candidate_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

