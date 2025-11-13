<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lat',
        'lng',
        'building_id',
        'capacity',
        'type',
        'longform'
    ];

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }

    public function examinationTimetables()
    {
        return $this->hasMany(ExaminationTimetable::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    // app/Models/Venue.php
public function scopeWithAvailability(Builder $query, ?int $semesterId = null)
{
    $semesterId = $semesterId ?? TimetableSemester::getFirstSemester()?->semester_id;

    if (! $semesterId) {
        return $query->select('id', 'name', 'capacity', 'type', 'longform')
                     ->addSelect(DB::raw('1 as free'))
                     ->addSelect(DB::raw('JSON_ARRAY() as booked_slots'));
    }

    return $query->select([
            'venues.id',
            'venues.name',
            'venues.capacity',
            'venues.type',
            'venues.longform',
        ])
        ->leftJoin('timetables', function ($join) use ($semesterId) {
            $join->on('timetables.venue_id', '=', 'venues.id')
                 ->where('timetables.semester_id', $semesterId);
        })
        ->selectRaw('
            CASE WHEN timetables.id IS NULL THEN 1 ELSE 0 END AS free
        ')
        ->selectRaw('
            COALESCE(
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        "day", timetables.day,
                        "from", DATE_FORMAT(timetables.time_start, "%H:%i"),
                        "to",   DATE_FORMAT(timetables.time_end,   "%H:%i"),
                        "sessions", cnt.cnt,
                        "courses",  cnt.courses
                    )
                ) FILTER (WHERE timetables.id IS NOT NULL),
                JSON_ARRAY()
            ) AS booked_slots
        ')
        ->leftJoinSub(
            Timetable::select([
                    'venue_id',
                    'day',
                    'time_start',
                    'time_end',
                    DB::raw('COUNT(*) AS cnt'),
                    DB::raw('JSON_ARRAYAGG(course_code) AS courses')
                ])
                ->where('semester_id', $semesterId)
                ->groupBy('venue_id', 'day', 'time_start', 'time_end'),
            'cnt',
            fn ($join) => $join->on('cnt.venue_id', '=', 'venues.id')
                               ->on('cnt.day', '=', 'timetables.day')
                               ->on('cnt.time_start', '=', 'timetables.time_start')
                               ->on('cnt.time_end', '=', 'timetables.time_end')
        )
        ->groupBy('venues.id', 'venues.name', 'venues.capacity', 'venues.type', 'venues.longform');
}
}