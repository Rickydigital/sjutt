<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    protected $fillable = ['student_id', 'message', 'is_anonymous', 'status'];

    public function student()
    {
        return $this->belongsTo(Student::class)->withDefault([
            'name' => 'Anonymous',
            'reg_no' => 'N/A',
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Admin',
        ]);
    }
}