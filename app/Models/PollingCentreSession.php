<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollingCentreSession extends Model
{
    protected $fillable = [
        'polling_centre_id',
        'election_id',
        'student_id',
        'reg_no',
        'form4_index',
        'last_name',
        'status',
        'votes_cast',
        'session_token_hash',
        'expires_at',
        'completed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function pollingCentre()
    {
        return $this->belongsTo(PollingCentre::class);
    }

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}