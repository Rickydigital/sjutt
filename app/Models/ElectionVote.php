<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionVote extends Model
{
    protected $fillable = [
        'election_id',
        'election_position_id',
        'candidate_id',
        'student_id',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function electionPosition(): BelongsTo
    {
        return $this->belongsTo(ElectionPosition::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(ElectionCandidate::class, 'candidate_id');
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function candidates()
{
    return $this->hasMany(\App\Models\ElectionCandidate::class, 'election_position_id');
}

public function votes()
{
    return $this->hasMany(\App\Models\ElectionVote::class, 'election_position_id');
}

}
