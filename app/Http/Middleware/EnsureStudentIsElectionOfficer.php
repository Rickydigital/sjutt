<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;

class EnsureStudentIsElectionOfficer
{
    public function handle(Request $request, Closure $next)
    {
        $student = auth('stuofficer')->user();


        // ✅ Important: type assertion for IDE + safety
        if (!$student instanceof Student) {
            abort(403, 'Unauthorized.');
        }

        $election = $request->route('election');

        if ($election) {
            $isOfficerForElection = $election->generalOfficers()
                ->where('students.id', $student->id)
                ->wherePivot('is_active', 1)
                ->exists();

            abort_if(!$isOfficerForElection, 403, 'You are not assigned as officer for this election.');

            return $next($request);
        }

        $isOfficerAny = $student->generalOfficerElections()
            ->wherePivot('is_active', 1)
            ->exists();

        abort_if(!$isOfficerAny, 403, 'You are not assigned as an election officer.');

        return $next($request);
    }
}
