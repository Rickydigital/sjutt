<?php

namespace App\Http\Controllers\Admin;

use App\Models\Query;
use App\Models\QueryProgress;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class QueryController extends Controller
{
    public function index()
    {
        // Fetch all queries
        $queries = Query::with('progress')->get();

        return view('admin.queries.index', compact('queries'));
    }

    public function addProgress(Request $request, $queryId)
    {
        // Validate the incoming request
        $request->validate([
            'admin_description' => 'required|string',
        ]);

        // Find the query
        $query = Query::findOrFail($queryId);

        // Update the status of the query
        $query->status = 'Investigation'; // Change as needed (to Processed, etc.)
        $query->save();

        // Create a progress record
        QueryProgress::create([
            'query_id' => $query->id,
            'admin_description' => $request->admin_description,
        ]);

        return redirect()->route('admin.queries.index')->with('message', 'Progress added successfully');
    }
}
