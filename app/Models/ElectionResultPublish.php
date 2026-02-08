<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionResultPublish extends Model
{
    protected $fillable = [
        'election_id',
        'published_by',
        'published_at',
        'version',
        'notes',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function publisher()
{
    return $this->belongsTo(\App\Models\Student::class, 'published_by');
}


    public function scopes(): HasMany
    {
        return $this->hasMany(ElectionResultScope::class, 'result_publish_id');
    }
}
