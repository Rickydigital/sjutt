<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionCandidate extends Model
{
    protected $fillable = [
        'election_position_id',
        'student_id',
        'faculty_id',
        'program_id',
        'photo',
        'description',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function electionPosition(): BelongsTo
    {
        return $this->belongsTo(ElectionPosition::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function vice()
    {
        return $this->hasOne(ElectionViceCandidate::class, 'election_candidate_id');
    }

    /**
     * Candidate must match the position scope and student scope.
     * Use this before approving candidate.
     */
    public function matchesScope(): bool
    {
        $pos = $this->electionPosition;

        return match ($pos->scope_type) {
            'global' => is_null($this->faculty_id) && is_null($this->program_id),

            'faculty' => !is_null($this->faculty_id)
                && $this->student?->faculty_id === $this->faculty_id,

            'program' => !is_null($this->program_id)
                && $this->student?->program_id === $this->program_id,

            default => false,
        };
    }
}
