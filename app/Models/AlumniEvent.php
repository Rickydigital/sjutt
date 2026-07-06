<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AlumniEvent extends Model
{
    protected $fillable = [
        'title','slug','description','venue','location','starts_at','ends_at','banner','status',
        'requires_registration','capacity','created_by','published_by','published_at'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'published_at' => 'datetime',
        'requires_registration' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            if (!$event->slug) $event->slug = Str::slug($event->title).'-'.Str::random(6);
        });
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function publisher(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
    public function calendars(): HasMany { return $this->hasMany(AlumniCalendar::class); }
}
