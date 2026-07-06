<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlumniElection extends Model
{
    protected $fillable = [
        'title','description','application_start_at','application_end_at','voting_start_at','voting_end_at',
        'status','created_by','assigned_officer_id','is_active','published_at'
    ];

    protected $casts = [
        'application_start_at' => 'datetime',
        'application_end_at' => 'datetime',
        'voting_start_at' => 'datetime',
        'voting_end_at' => 'datetime',
        'published_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function assignedOfficer(): BelongsTo { return $this->belongsTo(User::class, 'assigned_officer_id'); }
    public function positions(): HasMany { return $this->hasMany(AlumniElectionPosition::class); }
    public function officers(): HasMany { return $this->hasMany(AlumniElectionOfficer::class); }
    public function candidates(): HasMany { return $this->hasMany(AlumniElectionCandidate::class); }
    public function votes(): HasMany { return $this->hasMany(AlumniElectionVote::class); }

    public function isApplicationOpen(): bool
    {
        return $this->status === 'application_open'
            && (!$this->application_start_at || now()->gte($this->application_start_at))
            && (!$this->application_end_at || now()->lte($this->application_end_at));
    }

    public function isVotingOpen(): bool
    {
        return $this->status === 'voting_open'
            && (!$this->voting_start_at || now()->gte($this->voting_start_at))
            && (!$this->voting_end_at || now()->lte($this->voting_end_at));
    }

    public function canBeOverseenBy(User $user): bool
    {
        return $user->can('manage alumni elections')
            || $this->assigned_officer_id === $user->id
            || $this->officers()->where('user_id', $user->id)->where('is_active', true)->exists();
    }
}
