<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEventProgram extends Model
{
    protected $fillable = [
        'calendar_event_id',
        'program',
        'custom_week_number',
    ];

    public function event()
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }
}