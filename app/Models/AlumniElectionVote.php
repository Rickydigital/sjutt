<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniElectionVote extends Model
{
    protected $fillable = ['alumni_election_id','alumni_election_position_id','candidate_id','alumni_id','vote_hmac','voted_at'];
    protected $casts = ['voted_at' => 'datetime'];

    public function election(): BelongsTo { return $this->belongsTo(AlumniElection::class, 'alumni_election_id'); }
    public function position(): BelongsTo { return $this->belongsTo(AlumniElectionPosition::class, 'alumni_election_position_id'); }
    public function candidate(): BelongsTo { return $this->belongsTo(AlumniElectionCandidate::class, 'candidate_id'); }
    public function alumni(): BelongsTo { return $this->belongsTo(Alumni::class); }
}
