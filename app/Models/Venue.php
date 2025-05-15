<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lat',
        'lng',
        'building_id',
        'capacity',
        'type',
        'longform'
    ];

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }

    public function examinationTimetables()
    {
        return $this->hasMany(ExaminationTimetable::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}