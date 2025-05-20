<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarSetup extends Model
{
    protected $fillable = ['start_date', 'end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function events()
    {
        return $this->hasMany(CalendarEvent::class);
    }
}
    

