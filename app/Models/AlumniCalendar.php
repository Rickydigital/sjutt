<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniCalendar extends Model
{
    protected $fillable = [
        'title','description','calendar_date','start_time','end_time','venue','type','status','is_public','alumni_event_id','created_by'
    ];

    protected $casts = [
        'calendar_date' => 'date',
        'is_public' => 'boolean',
    ];

    public function event(): BelongsTo { return $this->belongsTo(AlumniEvent::class, 'alumni_event_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
