<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\TimetableSemester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TimetableSemesterController extends Controller
{
    public function index()
    {
        $setups = TimetableSemester::with('semester')
            ->latest('id')
            ->get();

        return response()->json([
            'current' => TimetableSemester::getCurrent(),
            'setups' => $setups,
        ]);
    }

   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'semester_id'   => 'required|exists:semesters,id',
        'academic_year' => 'required|string|max:255',
        'start_date'    => 'required|date|before_or_equal:end_date',
        'end_date'      => 'required|date|after_or_equal:start_date',
        'status'        => 'nullable|in:draft,active,archived',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $validator->validated();
    $data['status'] = $data['status'] ?? 'draft';

    $setup = null;

    DB::transaction(function () use (&$setup, $data) {
        $setup = TimetableSemester::create($data);

        if ($setup->status === 'active') {
            TimetableSemester::where('id', '!=', $setup->id)
                ->where('status', 'active')
                ->update(['status' => 'archived']);

            $setup->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);
        }
    });

    if (!$setup) {
        return response()->json([
            'errors' => ['setup' => 'Failed to create timetable setup.']
        ], 500);
    }

    return response()->json([
        'message' => 'Timetable setup created successfully.',
        'setup' => $setup->load('semester'),
    ]);
}

    public function show(TimetableSemester $timetableSemester)
    {
        $timetableSemester->load('semester');

        return response()->json([
            'id' => $timetableSemester->id,
            'semester_id' => $timetableSemester->semester_id,
            'academic_year' => $timetableSemester->academic_year,
            'start_date' => optional($timetableSemester->start_date)->format('Y-m-d'),
            'end_date' => optional($timetableSemester->end_date)->format('Y-m-d'),
            'status' => $timetableSemester->status,
            'activated_at' => optional($timetableSemester->activated_at)?->format('Y-m-d H:i:s'),
            'semester_name' => $timetableSemester->semester?->name,
        ]);
    }

    public function current()
    {
        $setup = TimetableSemester::getCurrent();

        if (!$setup) {
            return response()->json([
                'errors' => ['setup' => 'No active timetable setup found.']
            ], 422);
        }

        return response()->json($setup->load('semester'));
    }

    public function update(Request $request, TimetableSemester $timetableSemester)
    {
        $validator = Validator::make($request->all(), [
            'semester_id'   => 'required|exists:semesters,id',
            'academic_year' => 'required|string|max:255',
            'start_date'    => 'required|date|before_or_equal:end_date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'status'        => 'nullable|in:draft,active,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        DB::transaction(function () use ($timetableSemester, $data) {
            $timetableSemester->update($data);

            if (($data['status'] ?? null) === 'active') {
                TimetableSemester::where('id', '!=', $timetableSemester->id)
                    ->where('status', 'active')
                    ->update(['status' => 'archived']);

                $timetableSemester->update([
                    'status' => 'active',
                    'activated_at' => now(),
                ]);
            }
        });

        return response()->json([
            'message' => 'Timetable setup updated successfully.',
            'setup' => $timetableSemester->fresh()->load('semester'),
        ]);
    }

    public function activate(TimetableSemester $timetableSemester)
    {
        DB::transaction(function () use ($timetableSemester) {
            TimetableSemester::where('status', 'active')
                ->where('id', '!=', $timetableSemester->id)
                ->update(['status' => 'archived']);

            $timetableSemester->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Timetable setup activated successfully.',
            'setup' => $timetableSemester->fresh()->load('semester'),
        ]);
    }

    public function destroy(TimetableSemester $timetableSemester)
    {
        $hasTimetables = Timetable::where('semester_id', $timetableSemester->semester_id)->exists();

        if ($hasTimetables) {
            return response()->json([
                'errors' => ['setup' => 'This setup already has timetable entries. Archive it instead of deleting.']
            ], 422);
        }

        $timetableSemester->delete();

        return response()->json([
            'message' => 'Timetable setup deleted successfully.',
        ]);
    }
}