<?php

namespace App\Http\Controllers\Mobile;

use App\Models\Query;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class QueryController extends Controller
{
    public function create(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'description' => 'required|string',
            'date_sent' => 'required|date',
        ]);

        // Auto-generate the track number (e.g., SJUT-2025-00001A)
        $lastQuery = Query::latest()->first(); // Get the latest query for auto-increment
        $year = Carbon::now()->year;
        $count = $lastQuery ? intval(substr($lastQuery->track_number, -5)) + 1 : 1;
        $trackNumber = 'SJUT-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT) . 'A'; // You can adjust the 'A' as per logic

        // Create a new query record
        $query = Query::create([
            'description' => $request->description,
            'date_sent' => $request->date_sent,
            'status' => 'Received',
            'track_number' => $trackNumber,
        ]);

        return response()->json(['message' => 'Query submitted successfully', 'track_number' => $trackNumber]);
    }
}
