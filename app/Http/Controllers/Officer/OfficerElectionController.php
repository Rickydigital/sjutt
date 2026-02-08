<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OfficerElectionController extends Controller
{
   public function index()
{
    $student = auth('stuofficer')->user();

    $elections = $student->generalOfficerElections()
        ->wherePivot('is_active', 1)
        ->withCount('resultPublishes') // ✅ adds result_publishes_count
        ->latest()
        ->paginate(15);

    return view('officer.elections.index', compact('elections'));
}


    public function open(Request $request, Election $election)
    {
        // ────────────────────────────────────────────────
        // LOG IMMEDIATELY - top of method
        // ────────────────────────────────────────────────
        Log::info('OPEN METHOD REACHED', [
            'election_id'   => $election->id,
            'user_id'       => auth('stuofficer')->id(),
            'ip'            => $request->ip(),
            'time'          => now()->toDateTimeString(),
            'method'        => $request->method(),
        ]);

        $student = auth('stuofficer')->user();

        // Security check
        $isOfficer = $election->generalOfficers()
            ->where('students.id', $student->id)
            ->wherePivot('is_active', 1)
            ->exists();

        abort_if(!$isOfficer, 403, 'Not assigned for this election.');

        // More detailed debug
        $debugInfo = [
            'election_id'       => $election->id,
            'current_status'    => $election->status,
            'now'               => now()->toDateTimeString(),
            'today'             => now()->startOfDay()->toDateString(),
            'start_date'        => $election->start_date?->toDateString(),
            'end_date'          => $election->end_date?->toDateString(),
            'can_be_opened'     => $election->canBeOpened(),
            'status_is_draft'   => $election->status === 'draft',
            'today_lte_end'     => now()->startOfDay()->lte($election->end_date),
        ];

        Log::debug('OPEN - full condition check', $debugInfo);

        if (!$election->canBeOpened()) {
            Log::warning('OPEN blocked - canBeOpened() returned false', $debugInfo);
            return back()->with('error', 'You cannot open this election now. Check date or status.');
        }

        Log::info('OPEN - proceeding to update', ['election_id' => $election->id]);

        $election->update([
            'status' => 'open',
        ]);

        Log::info('Election successfully opened', ['election_id' => $election->id]);

        return back()->with('success', 'Election opened successfully.');
    }

    public function close(Request $request, Election $election)
    {
        // ────────────────────────────────────────────────
        // LOG IMMEDIATELY - top of method
        // ────────────────────────────────────────────────
        Log::info('CLOSE METHOD REACHED', [
            'election_id'   => $election->id,
            'user_id'       => auth('stuofficer')->id(),
            'ip'            => $request->ip(),
            'time'          => now()->toDateTimeString(),
            'method'        => $request->method(),
        ]);

        $student = auth('stuofficer')->user();

        $isOfficer = $election->generalOfficers()
            ->where('students.id', $student->id)
            ->wherePivot('is_active', 1)
            ->exists();

        abort_if(!$isOfficer, 403, 'Not assigned for this election.');

        $debugInfo = [
            'election_id'       => $election->id,
            'current_status'    => $election->status,
            'now'               => now()->toDateTimeString(),
            'today'             => now()->startOfDay()->toDateString(),
            'start_date'        => $election->start_date?->toDateString(),
            'end_date'          => $election->end_date?->toDateString(),
            'can_be_closed'     => $election->canBeClosed(),
            'status_is_open'    => $election->status === 'open',
            'today_lte_end'     => now()->startOfDay()->lte($election->end_date),
        ];

        Log::debug('CLOSE - full condition check', $debugInfo);

        if (!$election->canBeClosed()) {
            Log::warning('CLOSE blocked - canBeClosed() returned false', $debugInfo);
            return back()->with('error', 'You cannot close this election now. Check status or date.');
        }

        Log::info('CLOSE - proceeding to update', ['election_id' => $election->id]);

        $election->update([
            'status'    => 'closed',
            'is_active' => false,
        ]);

        Log::info('Election successfully closed', ['election_id' => $election->id]);

        return back()->with('success', 'Election closed successfully.');
    }
}