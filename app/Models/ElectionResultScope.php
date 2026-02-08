<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionResultScope extends Model
{
    protected $fillable = [
        'result_publish_id',
        'scope_type',
        'faculty_id',
        'program_id',
        'eligible_students',
        'voters',
        'turnout_percent',
    ];

    protected $casts = [
        'turnout_percent' => 'decimal:2',
    ];

    public function publish(): BelongsTo
    {
        return $this->belongsTo(ElectionResultPublish::class, 'result_publish_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(ElectionResultPosition::class, 'result_scope_id');
    }
}
