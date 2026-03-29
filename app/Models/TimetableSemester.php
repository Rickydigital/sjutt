<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TimetableSemester extends Model
{
    protected $fillable = [
        'semester_id',
        'academic_year',
        'start_date',
        'end_date',
        'status',
        'activated_at',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'activated_at' => 'datetime',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    public static function getCurrent(): ?self
    {
        return self::with('semester')
            ->active()
            ->latest('activated_at')
            ->latest('id')
            ->first();
    }

    public static function requireCurrent(): self
    {
        return self::with('semester')
            ->active()
            ->latest('activated_at')
            ->latest('id')
            ->firstOrFail();
    }

    /**
     * Backward compatibility.
     * You can remove this later after updating all controllers.
     */
    public static function getFirstSemester(): self
    {
        return self::requireCurrent();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function activate(): void
    {
        static::query()
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->update([
                'status' => 'archived',
            ]);

        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }
}