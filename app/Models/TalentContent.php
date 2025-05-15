<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TalentContent extends Model
{
    protected $fillable = [
        'student_id',
        'content_type',
        'file_path',
        'description',
        'social_media_link',
        'status',
    ];

    protected $casts = [
        'content_type' => 'string',
        'status' => 'string',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(TalentLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TalentComment::class);
    }

    public function flaggedContent(): HasOne
    {
        return $this->hasOne(FlaggedContent::class);
    }
}