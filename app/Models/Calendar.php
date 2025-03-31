<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $fillable = [
        'month', 'dates', 'academic_calendar', 'meeting_activities_calendar', 'academic_year'
    ];

    public function weekNumbers()
    {
        return $this->hasMany(WeekNumber::class);
    }
}