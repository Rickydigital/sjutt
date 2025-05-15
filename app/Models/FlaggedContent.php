<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlaggedContent extends Model
{
    protected $fillable = ['talent_content_id', 'reason', 'flagged_by'];

    public function talentContent(): BelongsTo
    {
        return $this->belongsTo(TalentContent::class);
    }
}