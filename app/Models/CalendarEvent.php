<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $fillable = [
        'calendar_setup_id',
        'event_date',
        'category',
        'event_description',
        'week_number',
    ];

    public function setup()
    {
        return $this->belongsTo(CalendarSetup::class, 'calendar_setup_id');
    }

    public function programs()
    {
        return $this->hasMany(CalendarEventProgram::class, 'calendar_event_id');
    }
}