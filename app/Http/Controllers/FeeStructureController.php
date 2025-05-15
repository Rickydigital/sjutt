<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use Illuminate\Http\Request;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $feeStructures = FeeStructure::when($search, function ($query, $search) {
            return $query->where('program_type', 'like', "%{$search}%")
                        ->orWhere('program_name', 'like', "%{$search}%");
        })->get();

        return response()->json([
            'success' => true,
            'data' => $feeStructures
        ], 200);
    }
}