<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlumniElectionPosition extends Model
{
    protected $fillable = ['alumni_election_id','name','description','max_candidates','max_votes_per_alumni','is_enabled'];
    protected $casts = ['is_enabled' => 'boolean'];

    public function election(): BelongsTo { return $this->belongsTo(AlumniElection::class, 'alumni_election_id'); }
    public function candidates(): HasMany { return $this->hasMany(AlumniElectionCandidate::class); }
    public function votes(): HasMany { return $this->hasMany(AlumniElectionVote::class); }
}
