<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $fillable = [
        'month', 'dates', 'academic_calendar', 'meeting_activities_calendar', 'academic_year'
    ];

    protected $casts = [
        'academic_year' => 'integer',
    ];

    public function weekNumbers()
    {
        return $this->hasMany(WeekNumber::class);
    }

    public static function getTodayEvents()
    {
        $today = Carbon::today('Africa/Dar_es_Salaam');
        $monthName = $today->format('F');
        $day = $today->day;
        $year = $today->year;

        return self::where('month', $monthName)
            ->where('academic_year', $year)
            ->where(function ($query) use ($day) {
                $query->where('dates', 'LIKE', "%$day%")
                    ->orWhere('dates', 'LIKE', "%-$day");
            })
            ->get()
            ->map(function ($event) {
                $events = [];
                if ($event->academic_calendar) {
                    $events[] = [
                        'type' => 'academic',
                        'description' => $event->academic_calendar,
                    ];
                }
                if ($event->meeting_activities_calendar) {
                    $events[] = [
                        'type' => 'meeting',
                        'description' => $event->meeting_activities_calendar,
                    ];
                }
                return $events;
            })
            ->flatten(1);
    }
}