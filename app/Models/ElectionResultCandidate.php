<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionResultCandidate extends Model
{
    protected $fillable = [
        'result_position_id',
        'election_candidate_id',
        'candidate_name',
        'candidate_reg_no',
        'vote_count',
        'vote_percent',
        'rank',
        'is_winner',
    ];

    protected $casts = [
        'vote_percent' => 'decimal:2',
        'is_winner' => 'boolean',
    ];

    public function resultPosition(): BelongsTo
    {
        return $this->belongsTo(ElectionResultPosition::class, 'result_position_id');
    }

    public function electionCandidate(): BelongsTo
    {
        return $this->belongsTo(ElectionCandidate::class, 'election_candidate_id');
    }

    public function breakdowns(): HasMany
    {
        return $this->hasMany(ElectionResultCandidateBreakdown::class, 'result_candidate_id');
    }
}
