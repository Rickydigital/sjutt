<?php

namespace App\Http\Controllers\Mobile\Alumni;

use App\Http\Controllers\Controller;
use App\Models\AlumniElection;
use App\Models\AlumniElectionCandidate;
use App\Models\AlumniElectionPosition;
use App\Models\AlumniElectionVote;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AlumniElectionController extends Controller
{
    public function elections(Request $request)
    {
        $elections = AlumniElection::query()
            ->where('is_active', true)
            ->whereIn('status', ['application_open','voting_open','closed','published'])
            ->with(['positions' => fn ($q) => $q->where('is_enabled', true)])
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => ['elections' => $elections]]);
    }

    public function show(Request $request, AlumniElection $alumniElection)
    {
        $alumniElection->load(['positions' => function ($q) {
            $q->where('is_enabled', true)->with(['candidates' => fn ($c) => $c->where('application_status', 'approved')->with('alumni')]);
        }]);

        return response()->json(['status' => 'success', 'data' => ['election' => $alumniElection]]);
    }

    public function apply(Request $request, AlumniElection $alumniElection)
    {
        $alumni = $request->user();

        if (!$alumni->isProfileComplete()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your profile must be complete before you can apply as a candidate.',
                'requires_profile_update' => true,
            ], 403);
        }

        if (!$alumniElection->isApplicationOpen()) {
            return response()->json(['status' => 'error', 'message' => 'Applications are not open.'], 403);
        }

        $existingApplication = AlumniElectionCandidate::where('alumni_election_id', $alumniElection->id)
            ->where('alumni_id', $alumni->id)
            ->whereIn('application_status', ['pending', 'approved'])
            ->first();

        if ($existingApplication) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already submitted an application for this election.',
            ], 422);
        }

        $data = $request->validate([
            'alumni_election_position_id' => 'required|exists:alumni_election_positions,id',
            'photo' => 'nullable|image|max:4096',
            'surname' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'sex' => 'nullable|string|max:30',
            'marital_status' => 'nullable|string|max:50',
            'education_level' => 'nullable|string|max:255',
            'current_position' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'applicant_type' => 'required|in:staff_academic,staff_non_academic,alumni',
            'institution_attended' => 'nullable|string|max:255',
            'programme_studied' => 'nullable|string|max:255',
            'year_graduated' => 'nullable|string|max:255',
            'sponsors' => 'nullable|array|min:0|max:10',
            'sponsors.*.name' => 'required_with:sponsors|string|max:255',
            'sponsors.*.faculty_school_directorate' => 'nullable|string|max:255',
            'sponsors.*.registration_no' => 'nullable|string|max:255',
        ]);

        $position = AlumniElectionPosition::where('id', $data['alumni_election_position_id'])
            ->where('alumni_election_id', $alumniElection->id)
            ->where('is_enabled', true)
            ->firstOrFail();

        if ($position->max_candidates && $position->candidates()->where('application_status', 'approved')->count() >= $position->max_candidates) {
            return response()->json(['status' => 'error', 'message' => 'This position already reached maximum approved candidates.'], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = ImageService::storeAsWebP(
                $request->file('photo'),
                'alumni/election-candidates',
                quality: 85,
                maxWidth: 600
            );
        }

        $candidate = DB::transaction(function () use ($data, $alumniElection, $alumni, $photoPath) {
            $candidate = AlumniElectionCandidate::updateOrCreate(
                [
                    'alumni_election_id' => $alumniElection->id,
                    'alumni_election_position_id' => $data['alumni_election_position_id'],
                    'alumni_id' => $alumni->id,
                ],
                array_merge(collect($data)->except(['sponsors','photo'])->toArray(), [
                    'photo' => $photoPath,
                    'application_status' => 'pending',
                    'rejection_reason' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                ])
            );

            $candidate->sponsors()->delete();
            foreach (($data['sponsors'] ?? []) as $sponsor) {
                $candidate->sponsors()->create($sponsor);
            }

            return $candidate->load(['position','sponsors']);
        });

        return response()->json(['status' => 'success', 'message' => 'Application submitted for review.', 'data' => ['candidate' => $candidate]], 201);
    }

    public function myApplications(Request $request)
    {
        $applications = AlumniElectionCandidate::with(['election','position','sponsors'])
            ->where('alumni_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => ['applications' => $applications]]);
    }

    public function voting(Request $request, AlumniElection $alumniElection)
    {
        if (!$alumniElection->isVotingOpen()) {
            return response()->json(['status' => 'error', 'message' => 'Voting is not open.'], 403);
        }

        $alumni = $request->user();
        $votedPositionIds = AlumniElectionVote::where('alumni_election_id', $alumniElection->id)
            ->where('alumni_id', $alumni->id)
            ->pluck('alumni_election_position_id');

        $positions = $alumniElection->positions()
            ->where('is_enabled', true)
            ->whereNotIn('id', $votedPositionIds)
            ->with(['candidates' => fn ($q) => $q->where('application_status', 'approved')->with('alumni')])
            ->get();

        return response()->json(['status' => 'success', 'data' => ['election' => $alumniElection, 'positions' => $positions]]);
    }

    public function vote(Request $request, AlumniElection $alumniElection)
    {
        $alumni = $request->user();

        if (!$alumni->isProfileComplete()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your profile must be complete before you can vote.',
                'requires_profile_update' => true,
            ], 403);
        }

        if (!$alumniElection->isVotingOpen()) {
            return response()->json(['status' => 'error', 'message' => 'Voting is not open.'], 403);
        }

        $data = $request->validate([
            'alumni_election_position_id' => 'required|exists:alumni_election_positions,id',
            'candidate_id' => 'required|exists:alumni_election_candidates,id',
        ]);

        return DB::transaction(function () use ($data, $alumniElection, $alumni) {
            $position = AlumniElectionPosition::where('id', $data['alumni_election_position_id'])
                ->where('alumni_election_id', $alumniElection->id)
                ->where('is_enabled', true)
                ->lockForUpdate()
                ->firstOrFail();

            $candidate = AlumniElectionCandidate::where('id', $data['candidate_id'])
                ->where('alumni_election_id', $alumniElection->id)
                ->where('alumni_election_position_id', $position->id)
                ->where('application_status', 'approved')
                ->firstOrFail();

            $already = AlumniElectionVote::where('alumni_election_id', $alumniElection->id)
                ->where('alumni_election_position_id', $position->id)
                ->where('alumni_id', $alumni->id)
                ->exists();

            if ($already) {
                return response()->json(['status' => 'error', 'message' => 'You already voted for this position.'], 422);
            }

            $secret = config('vote.hmac_secret', config('app.key'));
            $hmac = hash_hmac('sha256', implode('|', [$alumniElection->id, $position->id, $candidate->id, $alumni->id]), $secret);

            $vote = AlumniElectionVote::create([
                'alumni_election_id' => $alumniElection->id,
                'alumni_election_position_id' => $position->id,
                'candidate_id' => $candidate->id,
                'alumni_id' => $alumni->id,
                'vote_hmac' => $hmac,
                'voted_at' => now(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'Vote submitted successfully.', 'data' => ['vote' => $vote]], 201);
        });
    }

    public function results(Request $request, AlumniElection $alumniElection)
    {
        if (!in_array($alumniElection->status, ['published','closed'])) {
            return response()->json(['status' => 'error', 'message' => 'Results are not available.'], 403);
        }

        $positions = $alumniElection->positions()->with(['candidates' => function ($q) {
            $q->where('application_status', 'approved')->with('alumni')->withCount('votes')->orderByDesc('votes_count');
        }])->get();

        return response()->json(['status' => 'success', 'data' => ['election' => $alumniElection, 'positions' => $positions]]);
    }
}
