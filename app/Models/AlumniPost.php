<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AlumniPost extends Model
{
    protected $fillable = [
        'title','slug','body','image','category','status','postable_type','postable_id','created_by','approved_by','approved_at','published_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $post) {
            if (!$post->slug) $post->slug = Str::slug($post->title).'-'.Str::random(6);
        });
    }

    public function postable(): MorphTo { return $this->morphTo(); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
