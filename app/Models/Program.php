<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'total_years',
        'description',
        'administrator_id'
    ];

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrator_id')
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'Administrator');
                    });
    }

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }

       
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }
    
    public function getGeneratedFacultyNames(): array
    {
        if (!$this->short_name || !$this->total_years) {
            return [];
        }

        $names = [];
        for ($year = 1; $year <= $this->total_years; $year++) {
            $names[] = "{$this->short_name} {$year}";
        }
        return $names;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProgramCategory::class, 'program_category_program');
    }

    public function students() {
        return $this->hasMany(Student::class);
    }
}