<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Models\AlumniElection;
use App\Models\AlumniElectionOfficer;
use App\Models\AlumniElectionPosition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AlumniElectionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware('permission:view alumni elections')->only(['index','show','results']);
        $this->middleware('permission:manage alumni elections')->except(['index','show','results']);
    }

    public function index(Request $request)
    {
        $query = AlumniElection::with(['assignedOfficer','creator'])->withCount(['positions','candidates','votes']);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $elections = $query->latest()->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get(['id','name','email']);

        return view('alumni-elections.index', compact('elections','users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'application_start_at' => 'nullable|date',
            'application_end_at' => 'nullable|date|after_or_equal:application_start_at',
            'voting_start_at' => 'nullable|date',
            'voting_end_at' => 'nullable|date|after_or_equal:voting_start_at',
            'assigned_officer_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
        ]);

        $data['created_by'] = Auth::id();
        $data['is_active'] = $request->boolean('is_active', true);

        $election = AlumniElection::create($data);

        if (!empty($data['assigned_officer_id'])) {
            AlumniElectionOfficer::updateOrCreate(
                ['alumni_election_id' => $election->id, 'user_id' => $data['assigned_officer_id']],
                ['role' => 'officer', 'is_active' => true]
            );
        }

        return back()->with('success', 'Alumni election created successfully.');
    }

    public function show(AlumniElection $alumniElection)
    {
        $this->authorizeOfficerAccess($alumniElection);

        $alumniElection->load([
            'positions.candidates.alumni',
            'officers.user',
            'assignedOfficer',
            'candidates.position',
            'candidates.alumni',
            'candidates.sponsors',
        ])->loadCount(['votes','candidates']);

        $users = User::orderBy('name')->get(['id','name','email']);

        return view('alumni-elections.show', compact('alumniElection','users'));
    }

    public function update(Request $request, AlumniElection $alumniElection)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'application_start_at' => 'nullable|date',
            'application_end_at' => 'nullable|date|after_or_equal:application_start_at',
            'voting_start_at' => 'nullable|date',
            'voting_end_at' => 'nullable|date|after_or_equal:voting_start_at',
            'assigned_officer_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $alumniElection->update($data);

        if (!empty($data['assigned_officer_id'])) {
            AlumniElectionOfficer::updateOrCreate(
                ['alumni_election_id' => $alumniElection->id, 'user_id' => $data['assigned_officer_id']],
                ['role' => 'officer', 'is_active' => true]
            );
        }

        return back()->with('success', 'Election updated successfully.');
    }

    public function destroy(AlumniElection $alumniElection)
    {
        if ($alumniElection->votes()->exists()) {
            return back()->with('error', 'Cannot delete election with votes. Close or publish it instead.');
        }
        $alumniElection->delete();
        return redirect()->route('alumni-elections.index')->with('success', 'Election deleted.');
    }

    public function storePosition(Request $request, AlumniElection $alumniElection)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_candidates' => 'nullable|integer|min:1',
            'max_votes_per_alumni' => 'required|integer|min:1|max:10',
            'is_enabled' => 'nullable|boolean',
        ]);

        $alumniElection->positions()->create([
            'name' => $request->name,
            'description' => $request->description,
            'max_candidates' => $request->max_candidates,
            'max_votes_per_alumni' => $request->max_votes_per_alumni,
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

        return back()->with('success', 'Position added.');
    }

    public function updatePosition(Request $request, AlumniElectionPosition $position)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_candidates' => 'nullable|integer|min:1',
            'max_votes_per_alumni' => 'required|integer|min:1|max:10',
            'is_enabled' => 'nullable|boolean',
        ]);

        $position->update([
            'name' => $request->name,
            'description' => $request->description,
            'max_candidates' => $request->max_candidates,
            'max_votes_per_alumni' => $request->max_votes_per_alumni,
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return back()->with('success', 'Position updated.');
    }

    public function deletePosition(AlumniElectionPosition $position)
    {
        if ($position->votes()->exists()) {
            return back()->with('error', 'Cannot delete a position with votes.');
        }
        $position->delete();
        return back()->with('success', 'Position deleted.');
    }

    public function assignOfficer(Request $request, AlumniElection $alumniElection)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:officer,supervisor,verifier',
        ]);

        AlumniElectionOfficer::updateOrCreate(
            ['alumni_election_id' => $alumniElection->id, 'user_id' => $data['user_id']],
            ['role' => $data['role'], 'is_active' => true]
        );

        if (!$alumniElection->assigned_officer_id) {
            $alumniElection->update(['assigned_officer_id' => $data['user_id']]);
        }

        return back()->with('success', 'Election officer assigned. Officer can now oversee this election.');
    }

    public function removeOfficer(AlumniElectionOfficer $officer)
    {
        $officer->update(['is_active' => false]);
        return back()->with('success', 'Officer removed from active oversight.');
    }

    public function changeStatus(Request $request, AlumniElection $alumniElection)
    {
        $data = $request->validate([
            'status' => 'required|in:draft,application_open,application_closed,voting_open,closed,published',
        ]);

        $update = ['status' => $data['status']];
        if ($data['status'] === 'published') {
            $update['published_at'] = now();
        }
        $alumniElection->update($update);

        return back()->with('success', 'Election status changed to ' . str_replace('_', ' ', $data['status']) . '.');
    }

    public function results(AlumniElection $alumniElection)
    {
        $this->authorizeOfficerAccess($alumniElection);

        $positions = $alumniElection->positions()
            ->with(['candidates' => function ($q) {
                $q->where('application_status', 'approved')->withCount('votes')->orderByDesc('votes_count');
            }])->get();

        return view('alumni-elections.results', compact('alumniElection','positions'));
    }

    private function authorizeOfficerAccess(AlumniElection $election): void
    {
        $user = Auth::user();
        if (!$user || !$election->canBeOverseenBy($user)) {
            abort(403, 'You are not assigned to oversee this election.');
        }
    }
}
