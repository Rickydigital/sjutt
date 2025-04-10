<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'lat', 'lng'];

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }

    public function examinationTimetables()
    {
        return $this->hasMany(ExaminationTimetable::class);
    }
}