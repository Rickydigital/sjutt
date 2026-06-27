<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollingCentre extends Model
{
    protected $fillable = [
        'election_id',
        'name',
        'location',
        'manager_name',
        'manager_phone',
        'manager_email',
        'public_token_hash',
        'active_from',
        'active_until',
        'is_active',
        'successful_sessions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function sessions()
    {
        return $this->hasMany(\App\Models\PollingCentreSession::class);
    }

    public function isUsable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->active_from && now()->lt($this->active_from)) {
            return false;
        }

        if ($this->active_until && now()->gt($this->active_until)) {
            return false;
        }

        return $this->election
            && $this->election->status === 'open'
            && $this->election->isStillOpen();
    }
}