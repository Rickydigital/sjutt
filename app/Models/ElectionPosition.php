<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionPosition extends Model
{
    protected $fillable = [
        'election_id',
        'position_definition_id',
        'scope_type',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(PositionDefinition::class, 'position_definition_id');
    }

    public function faculties(): BelongsToMany
    {
        return $this->belongsToMany(
            Faculty::class,
            'election_position_faculty',
            'election_position_id',
            'faculty_id'
        )->withTimestamps();
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            Program::class,
            'election_position_program',
            'election_position_id',
            'program_id'
        )->withTimestamps();
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(ElectionCandidate::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ElectionVote::class);
    }

    

    /**
     * Check if a student is eligible to vote for THIS position instance
     * based on scope_type and pivot assignments.
     */
    public function isStudentEligible(Student $student): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        return match ($this->scope_type) {
            'global' => true,

            'faculty' => $student->faculty_id
                && $this->faculties()
                    ->where('faculties.id', $student->faculty_id)
                    ->exists(),

            'program' => $student->program_id
                && $this->programs()
                    ->where('programs.id', $student->program_id)
                    ->exists(),

            default => false,
        };
    }
}
