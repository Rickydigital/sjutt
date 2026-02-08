<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionResultCandidateBreakdown extends Model
{
    protected $fillable = [
        'result_candidate_id',
        'scope_type',
        'faculty_id',
        'program_id',
        'vote_count',
        'vote_percent',
    ];

    protected $casts = [
        'vote_percent' => 'decimal:2',
    ];

    public function resultCandidate(): BelongsTo
    {
        return $this->belongsTo(ElectionResultCandidate::class, 'result_candidate_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
