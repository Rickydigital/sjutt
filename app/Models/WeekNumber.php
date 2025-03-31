<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeekNumber extends Model
{
    protected $fillable = [
        'calendar_id', 'week_number', 'program_category'
    ];

    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }
}