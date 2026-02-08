<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionResultPosition extends Model
{
    protected $fillable = [
        'result_scope_id',
        'election_position_id',
        'position_name',
        'eligible_students',
        'voters',
        'turnout_percent',
        'winner_candidate_id',
    ];

    protected $casts = [
        'turnout_percent' => 'decimal:2',
    ];

    public function scope(): BelongsTo
    {
        return $this->belongsTo(ElectionResultScope::class, 'result_scope_id');
    }

    public function electionPosition(): BelongsTo
    {
        return $this->belongsTo(ElectionPosition::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(ElectionCandidate::class, 'winner_candidate_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(ElectionResultCandidate::class, 'result_position_id');
    }
}
