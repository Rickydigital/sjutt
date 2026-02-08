<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\ElectionViceCandidate;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectionCandidateController extends Controller
{
    public function index(Election $election)
    {
        $positions = $election->positions()
            ->with(['definition', 'faculties', 'programs'])
            ->orderBy('id', 'desc')
            ->get();

        // Candidates grouped by position
        $candidates = ElectionCandidate::query()
            ->whereHas('electionPosition', fn($q) => $q->where('election_id', $election->id))
            ->with([
                'student.faculty',
                'student.program',
                'vice.student.faculty',
                'vice.student.program',
                'electionPosition.definition',
            ])
            ->latest()
            ->get()
            ->groupBy('election_position_id');

        // Students already used in this election (any position) -> cannot be main candidate elsewhere
        $usedStudentIds = ElectionCandidate::query()
            ->whereHas('electionPosition', fn($q) => $q->where('election_id', $election->id))
            ->pluck('student_id');

        $students = Student::query()
            ->where('status', 'Active')
            ->whereNotIn('id', $usedStudentIds)
            ->orderBy('first_name')
            ->get();

        return view('officer.elections.candidates', compact('election', 'positions', 'candidates', 'students'));
    }

    public function store(Request $request, Election $election)
    {
        $validated = $request->validate([
            'election_position_id' => 'required|exists:election_positions,id',
            'student_id'           => 'required|exists:students,id',
            'photo'                => 'nullable|image|max:2048',
            'description'          => 'nullable|string|max:2000',

            // Vice (optional)
            'vice_student_id'      => 'nullable|exists:students,id|different:student_id',
            'vice_photo'           => 'nullable|image|max:2048',
            'vice_description'     => 'nullable|string|max:2000',
        ]);

        // Verify position belongs to this election
        $position = ElectionPosition::query()
            ->where('id', $validated['election_position_id'])
            ->where('election_id', $election->id)
            ->firstOrFail();

        $student = Student::findOrFail($validated['student_id']);

        if ($student->status !== 'Active') {
            return back()->withErrors(['student_id' => 'Student is not active.'])->withInput();
        }

        // Check eligibility by scope
        if (!$this->isEligibleForPosition($position, $student)) {
            return back()->withErrors([
                'student_id' => 'This student is not eligible for this position based on scope.'
            ])->withInput();
        }

        // Allow updating same position (so you can add/change vice later),
        // but block if the same student is already candidate in ANOTHER position in same election.
        $alreadyCandidate = ElectionCandidate::query()
            ->whereHas('electionPosition', fn($q) => $q->where('election_id', $election->id))
            ->where('student_id', $student->id)
            ->where('election_position_id', '!=', $position->id)
            ->exists();

        if ($alreadyCandidate) {
            return back()->withErrors([
                'student_id' => 'This student is already a candidate in another position for this election.'
            ])->withInput();
        }

        // Enforce max candidates limit per scope target
        // Only enforce when creating a NEW candidate row for this position (not when updating same one)
        $existingCandidateForThisPosition = ElectionCandidate::query()
            ->where('election_position_id', $position->id)
            ->where('student_id', $student->id)
            ->exists();

        if (!$existingCandidateForThisPosition) {
            $this->enforceMaxCandidates($position, $student);
        }

        // Uploads (optional)
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('candidates', 'public');
        }

        $vicePhotoPath = null;
        if ($request->hasFile('vice_photo')) {
            $vicePhotoPath = $request->file('vice_photo')->store('candidates', 'public');
        }

        DB::transaction(function () use ($position, $student, $photoPath, $validated, $election, $vicePhotoPath) {

            // Build candidate data safely (do NOT overwrite existing photo with null)
            $candidateData = [
                'faculty_id'  => $student->faculty_id,
                'program_id'  => $student->program_id,
                'description' => $validated['description'] ?? null,

                // if you want every edit to re-set approval, keep false.
                // If you want approval to remain once approved, comment the next line.
                'is_approved' => false,
            ];

            if ($photoPath) {
                $candidateData['photo'] = $photoPath;
            }

            $candidate = ElectionCandidate::updateOrCreate(
                [
                    'election_position_id' => $position->id,
                    'student_id'           => $student->id,
                ],
                $candidateData
            );

            // ───────────────────────────────
            // Vice (optional)
            // ───────────────────────────────
            $viceStudentId = $validated['vice_student_id'] ?? null;

            if ($viceStudentId) {
                $viceStudent = Student::findOrFail($viceStudentId);

                if ($viceStudent->status !== 'Active') {
                    throw ValidationException::withMessages([
                        'vice_student_id' => 'Vice student is not active.',
                    ]);
                }

                // prevent vice from being a MAIN candidate in the same election
                $viceAlreadyCandidate = ElectionCandidate::query()
                    ->whereHas('electionPosition', fn($q) => $q->where('election_id', $election->id))
                    ->where('student_id', $viceStudent->id)
                    ->exists();

                if ($viceAlreadyCandidate) {
                    throw ValidationException::withMessages([
                        'vice_student_id' => 'This student is already a candidate in this election (cannot be vice).',
                    ]);
                }

                // prevent vice from being vice for someone else in same election
                $viceAlreadyVice = ElectionViceCandidate::query()
                    ->whereHas('candidate.electionPosition', fn($q) => $q->where('election_id', $election->id))
                    ->where('student_id', $viceStudent->id)
                    ->where('election_candidate_id', '!=', $candidate->id)
                    ->exists();

                if ($viceAlreadyVice) {
                    throw ValidationException::withMessages([
                        'vice_student_id' => 'This student is already assigned as vice in this election.',
                    ]);
                }

                // scope eligibility for vice (recommended)
                if (!$this->isEligibleForPosition($position, $viceStudent)) {
                    throw ValidationException::withMessages([
                        'vice_student_id' => 'Vice student is not eligible for this position scope.',
                    ]);
                }

                // Build vice data safely (do NOT overwrite existing vice photo with null)
                $viceData = [
                    'student_id'  => $viceStudent->id,
                    'faculty_id'  => $viceStudent->faculty_id,
                    'program_id'  => $viceStudent->program_id,
                    'description' => $validated['vice_description'] ?? null,
                ];

                if ($vicePhotoPath) {
                    $viceData['photo'] = $vicePhotoPath;
                }

                ElectionViceCandidate::updateOrCreate(
                    ['election_candidate_id' => $candidate->id],
                    $viceData
                );
            } else {
                // if no vice selected, remove existing vice (optional behavior)
                ElectionViceCandidate::where('election_candidate_id', $candidate->id)->delete();
            }
        });

        return back()->with('success', 'Candidate added successfully.');
    }

    public function destroy(Election $election, ElectionCandidate $candidate)
    {
        abort_if(
            $candidate->electionPosition?->election_id != $election->id,
            404,
            'Candidate not found in this election.'
        );

        $candidate->delete();

        return back()->with('success', 'Candidate removed successfully.');
    }

    private function isEligibleForPosition(ElectionPosition $position, Student $student): bool
    {
        $scope = $position->scope_type;

        if ($scope === 'global') {
            return true;
        }

        if ($scope === 'faculty') {
            if (!$student->faculty_id) return false;

            return $position->faculties()
                ->where('faculties.id', $student->faculty_id)
                ->exists();
        }

        if ($scope === 'program') {
            if (!$student->program_id) return false;

            return $position->programs()
                ->where('programs.id', $student->program_id)
                ->exists();
        }

        return false;
    }

    private function enforceMaxCandidates(ElectionPosition $position, Student $student): void
    {
        $max = (int) ($position->max_candidates ?? 0);
        if ($max <= 0) return;

        $scope = $position->scope_type;

        $query = ElectionCandidate::query()
            ->where('election_position_id', $position->id);

        if ($scope === 'faculty' && $student->faculty_id) {
            $query->where('faculty_id', $student->faculty_id);
        } elseif ($scope === 'program' && $student->program_id) {
            $query->where('program_id', $student->program_id);
        }

        $currentCount = $query->count();

        if ($currentCount >= $max) {
            $message = match ($scope) {
                'faculty' => "Maximum candidates reached for this faculty scope ({$max}).",
                'program' => "Maximum candidates reached for this program scope ({$max}).",
                default   => "Maximum candidates reached for this position ({$max}).",
            };

            throw ValidationException::withMessages([
                'student_id' => $message,
            ]);
        }
    }
}
