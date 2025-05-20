<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = [
        'year',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }
}