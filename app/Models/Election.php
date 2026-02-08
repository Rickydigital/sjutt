<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Election extends Model
{
    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'open_time',
        'close_time',
        'is_active',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
        // Do NOT cast open_time / close_time to datetime — keep as string
    ];

    public function positions(): HasMany
    {
        return $this->hasMany(ElectionPosition::class);
    }

   
public function resultPublishes(): HasMany
{
    return $this->hasMany(ElectionResultPublish::class, 'election_id');
}

// latest publish
public function latestResultPublish()
{
    return $this->hasOne(ElectionResultPublish::class, 'election_id')->latestOfMany('version');
}
    public function votes(): HasMany
    {
        return $this->hasMany(ElectionVote::class);
    }

    // ────────────────────────────────────────────────
    // Officer actions: open / close
    // ────────────────────────────────────────────────

    public function canBeOpened(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        // Allow any time before or on end_date
        return now()->startOfDay()->lte($this->end_date);
    }

    public function canBeClosed(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        // Allow any time before or on end_date
        return now()->startOfDay()->lte($this->end_date);
    }

    // ────────────────────────────────────────────────
    // Optional: student voting window (daily time slot)
    // ────────────────────────────────────────────────

    public function getOpenTimeTodayAttribute(): ?Carbon
    {
        if (!$this->open_time) return null;
        return Carbon::today()->setTimeFromTimeString($this->open_time);
    }

    public function getCloseTimeTodayAttribute(): ?Carbon
    {
        if (!$this->close_time) return null;
        return Carbon::today()->setTimeFromTimeString($this->close_time);
    }

    public function isVotingWindowOpen(): bool
    {
        $openToday  = $this->open_time_today;
        $closeToday = $this->close_time_today;

        if (!$openToday || !$closeToday) {
            return false;
        }

        $today = now()->startOfDay();
        if ($today->lt($this->start_date) || $today->gt($this->end_date)) {
            return false;
        }

        return now()->gte($openToday) && now()->lt($closeToday);
    }

    // If you still need full open/close datetime for other logic
    public function getOpenAtAttribute(): ?Carbon
    {
        if (!$this->start_date || !$this->open_time) return null;
        return Carbon::parse("{$this->start_date->format('Y-m-d')} {$this->open_time}");
    }

    public function getCloseAtAttribute(): ?Carbon
    {
        if (!$this->end_date || !$this->close_time) return null;
        return Carbon::parse("{$this->end_date->format('Y-m-d')} {$this->close_time}");
    }

    public function generalOfficers(): BelongsToMany
{
    return $this->belongsToMany(
        \App\Models\Student::class,
        'student_general_election_officers',  // pivot table
        'election_id',                        // FK on pivot referencing elections
        'student_id'                          // FK on pivot referencing students
    )->withPivot(['is_active'])->withTimestamps();
}
public function isStillOpen(): bool
{
    // Must be open status
    if (($this->status ?? null) !== 'open') return false;

    // If you store close_time and close_date separately:
    // close_time might be "14:00:00" and end_date "2026-02-04"
    if ($this->end_date && $this->close_time) {
        $closeAt = Carbon::parse($this->end_date->format('Y-m-d') . ' ' . $this->close_time);
        return now()->lessThanOrEqualTo($closeAt);
    }

    // If you only store end_date, treat end of day as closing time
    if ($this->end_date) {
        return now()->lessThanOrEqualTo($this->end_date->endOfDay());
    }

    // If no end date/time configured, fallback to is_active
    return (bool)($this->is_active ?? false);
}


}