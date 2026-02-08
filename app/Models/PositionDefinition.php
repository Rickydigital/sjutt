<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PositionDefinition extends Model
{
    protected $fillable = [
        'code',
        'name',
        'default_scope_type',
        'max_votes_per_voter',
        'description',
    ];

    protected $casts = [
        'max_votes_per_voter' => 'integer',
    ];

    public function electionPositions(): HasMany
    {
        return $this->hasMany(ElectionPosition::class);
    }
}
