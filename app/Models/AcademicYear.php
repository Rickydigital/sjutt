<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AcademicYear extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'activated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'activated_at' => 'datetime',
    ];

    public function almanacSetups()
    {
        return $this->hasMany(AlmanacSetup::class);
    }

    public function timetableSemesters()
    {
        return $this->hasMany(TimetableSemester::class);
    }

    public function examSetups()
    {
        return $this->hasMany(ExamSetup::class);
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
        return self::query()
            ->active()
            ->latest('activated_at')
            ->latest('id')
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function activate(): void
    {
        DB::transaction(function () {
            static::query()
                ->where('id', '!=', $this->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'archived',
                    'activated_at' => null,
                ]);

            $this->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);
        });
    }
}
