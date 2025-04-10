<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Year extends Model
{
    use HasFactory;

    protected $fillable = ['year'];

    public function timetables() {
        return $this->hasMany(Timetable::class);
    }

    public function examinationTimetables() {
        return $this->hasMany(ExaminationTimetable::class);
    }
}
