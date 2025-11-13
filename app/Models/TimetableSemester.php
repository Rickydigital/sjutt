<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableSemester extends Model
{
    protected $fillable = ['semester_id', 'academic_year', 'start_date', 'end_date'];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public static function getFirstSemester()
    {
        return self::with('semester')->orderBy('id')->firstOrFail();
    }
}