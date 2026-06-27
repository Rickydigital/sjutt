<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\PollingCentre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PollingCentreController extends Controller
{
    public function index(Election $election)
{
    $election->load(['pollingCentres' => function ($q) {
        $q->withCount([
            'sessions as total_sessions',
            'sessions as started_sessions' => fn ($s) => $s->where('status', 'started'),
            'sessions as reg_verified_sessions' => fn ($s) => $s->where('status', 'reg_verified'),
            'sessions as identity_verified_sessions' => fn ($s) => $s->where('status', 'identity_verified'),
            'sessions as completed_sessions' => fn ($s) => $s->where('status', 'completed'),
            'sessions as failed_sessions' => fn ($s) => $s->where('status', 'failed'),
            'sessions as expired_sessions' => fn ($s) => $s->where('status', 'expired'),
        ])->withSum('sessions as total_votes_cast', 'votes_cast')
          ->withMax('sessions as last_activity_at', 'updated_at');
    }]);

    $analytics = [
        'total_centres' => $election->pollingCentres->count(),
        'active_centres' => $election->pollingCentres->where('is_active', true)->count(),
        'inactive_centres' => $election->pollingCentres->where('is_active', false)->count(),
        'total_sessions' => $election->pollingCentres->sum('total_sessions'),
        'completed_sessions' => $election->pollingCentres->sum('completed_sessions'),
        'failed_sessions' => $election->pollingCentres->sum('failed_sessions'),
        'expired_sessions' => $election->pollingCentres->sum('expired_sessions'),
        'total_votes_cast' => $election->pollingCentres->sum('total_votes_cast'),
    ];

    return view('elections.polling-centres.index', compact('election', 'analytics'));
}

    public function store(Request $request, Election $election)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'location'      => 'nullable|string|max:255',
            'manager_name'  => 'nullable|string|max:255',
            'manager_phone' => 'nullable|string|max:30',
            'manager_email' => 'nullable|email|max:255',
            'active_from'   => 'nullable|date',
            'active_until'  => 'nullable|date|after_or_equal:active_from',
            'is_active'     => 'nullable|boolean',
        ]);

        $plainToken = Str::random(80);

        $centre = PollingCentre::create([
            'election_id'        => $election->id,
            'name'               => $validated['name'],
            'location'           => $validated['location'] ?? null,
            'manager_name'       => $validated['manager_name'] ?? null,
            'manager_phone'      => $validated['manager_phone'] ?? null,
            'manager_email'      => $validated['manager_email'] ?? null,
            'active_from'        => $validated['active_from'] ?? null,
            'active_until'       => $validated['active_until'] ?? null,
            'is_active'          => $request->has('is_active') ? $request->boolean('is_active') : true,
            'public_token_hash'  => hash('sha256', $plainToken),
        ]);

        $link = route('polling.public.show', $plainToken);

        return back()->with([
            'success' => 'Polling centre created successfully.',
            'polling_link' => $link,
            'polling_centre_id' => $centre->id,
        ]);
    }

    public function update(Request $request, Election $election, PollingCentre $pollingCentre)
    {
        abort_if((int) $pollingCentre->election_id !== (int) $election->id, 404);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'location'      => 'nullable|string|max:255',
            'manager_name'  => 'nullable|string|max:255',
            'manager_phone' => 'nullable|string|max:30',
            'manager_email' => 'nullable|email|max:255',
            'active_from'   => 'nullable|date',
            'active_until'  => 'nullable|date|after_or_equal:active_from',
            'is_active'     => 'nullable|boolean',
        ]);

        $pollingCentre->update([
            'name'          => $validated['name'],
            'location'      => $validated['location'] ?? null,
            'manager_name'  => $validated['manager_name'] ?? null,
            'manager_phone' => $validated['manager_phone'] ?? null,
            'manager_email' => $validated['manager_email'] ?? null,
            'active_from'   => $validated['active_from'] ?? null,
            'active_until'  => $validated['active_until'] ?? null,
            'is_active'     => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Polling centre updated successfully.');
    }

    public function regenerateLink(Election $election, PollingCentre $pollingCentre)
    {
        abort_if((int) $pollingCentre->election_id !== (int) $election->id, 404);

        $plainToken = Str::random(80);

        $pollingCentre->update([
            'public_token_hash' => hash('sha256', $plainToken),
        ]);

        $link = route('polling.public.show', $plainToken);

        return back()->with([
            'success' => 'Polling centre link regenerated successfully.',
            'polling_link' => $link,
            'polling_centre_id' => $pollingCentre->id,
        ]);
    }

    public function deactivate(Election $election, PollingCentre $pollingCentre)
    {
        abort_if((int) $pollingCentre->election_id !== (int) $election->id, 404);

        $pollingCentre->update([
            'is_active' => false,
        ]);

        return back()->with('success', 'Polling centre deactivated successfully.');
    }

    public function activate(Election $election, PollingCentre $pollingCentre)
    {
        abort_if((int) $pollingCentre->election_id !== (int) $election->id, 404);

        $pollingCentre->update([
            'is_active' => true,
        ]);

        return back()->with('success', 'Polling centre activated successfully.');
    }

    public function destroy(Election $election, PollingCentre $pollingCentre)
    {
        abort_if((int) $pollingCentre->election_id !== (int) $election->id, 404);

        $pollingCentre->delete();

        return back()->with('success', 'Polling centre deleted successfully.');
    }
}