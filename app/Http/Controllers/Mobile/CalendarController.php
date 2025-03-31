<?php

// app/Http/Controllers/Mobile/CalendarController.php
namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function fetch(Request $request)
    {
        $month = $request->query('month');
        $program_category = $request->query('program_category');

        $query = Calendar::with('weekNumbers');

        if ($month) {
            $query->where('month', $month);
        }
        if ($program_category) {
            $query->whereHas('weekNumbers', function ($q) use ($program_category) {
                $q->where('program_category', $program_category);
            });
        }

        $calendars = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $calendars
        ]);
    }
}
