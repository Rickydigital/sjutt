<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;
use Validator;

class VenueController extends Controller
{
    public function searchVenue(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'search' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $venue = Venue::where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('longform', 'like', '%' . $request->search . '%')
                    ->first();

        if (!$venue) {
            return response()->json([
                'status' => 'error',
                'message' => 'Venue not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Venue found',
            'data' => $venue
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Venue Search Error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Search failed',
        ], 500);
    }
}

public function getVenues(Request $request)
{
    try {
        $venues = Venue::all();

        return response()->json([
            'status' => 'success',
            'message' => 'Venues retrieved successfully',
            'data' => $venues
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Get Venues Error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to retrieve venues',
        ], 500);
    }
}
}
