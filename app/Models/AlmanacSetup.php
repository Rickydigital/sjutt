<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AlmanacSetup extends Model
{
    protected $fillable = [
        'academic_year_id',
        'title',
        'start_date',
        'end_date',
        'status',
        'activated_at',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'activated_at' => 'datetime',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function programGroups(): HasMany
    {
        return $this->hasMany(AlmanacProgramGroup::class)->orderBy('display_order');
    }

    public function weekBlocks(): HasMany
    {
        return $this->hasMany(AlmanacWeekBlock::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AlmanacEvent::class);
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
        return self::with('academicYear')
            ->active()
            ->latest('activated_at')
            ->latest('id')
            ->first();
    }

    public function activate(): void
    {
        DB::transaction(function (): void {
            self::query()
                ->whereKeyNot($this->getKey())
                ->where('status', 'active')
                ->update(['status' => 'archived']);

            $this->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);
        });
    }
}
