@php
$venueMap = collect($venues ?? [])->keyBy('id');
@endphp

<style>
    .tt-table-wrap {
        overflow-x: auto;
        background: var(--tt-surface);
    }

    .tt-table {
        width: 100%;
        min-width: 1120px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .tt-table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: linear-gradient(135deg, var(--tt-primary), var(--tt-secondary));
        color: #fff;
        border: 1px solid rgba(255, 255, 255, .15);
        padding: .9rem;
        font-weight: 700;
        text-align: center;
        vertical-align: middle;
    }

    .tt-table tbody td {
        border: 1px solid var(--tt-border);
        text-align: center;
        vertical-align: top;
        background: #fff;
        padding: .35rem;
    }

    .tt-time-cell {
        background: #faf7ff !important;
        font-weight: 700;
        color: var(--tt-primary);
        min-width: 120px;
        white-space: nowrap;
    }

    .tt-empty-cell {
        min-height: 105px;
        height: 105px;
        background: linear-gradient(180deg, #fcfbff, #f6f2ff);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: .2s ease;
        color: var(--tt-muted);
    }

    .tt-empty-cell:hover {
        background: linear-gradient(180deg, #f5efff, #ece4ff);
        color: var(--tt-primary);
        transform: translateY(-1px);
    }

    .tt-slot-stack {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }

    .tt-course-card {
        background: linear-gradient(180deg, #ffffff, #f8f6fd);
        border: 1px solid var(--tt-border);
        border-left: 5px solid var(--tt-secondary);
        border-radius: 14px;
        padding: .75rem;
        text-align: left;
        box-shadow: 0 5px 12px rgba(75, 46, 131, 0.08);
    }

    .tt-course-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .5rem;
        margin-bottom: .45rem;
    }

    .tt-course-code {
        font-weight: 800;
        color: var(--tt-primary);
        font-size: .95rem;
        line-height: 1.1;
    }

    .tt-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .2rem .55rem;
        font-size: .72rem;
        font-weight: 700;
    }

    .tt-badge-lecture {
        background: #e7f1ff;
        color: #0d6efd;
    }

    .tt-badge-practical {
        background: #fff2e0;
        color: #c56a00;
    }

    .tt-badge-workshop {
        background: #e6fff0;
        color: #198754;
    }

    .tt-course-meta {
        display: grid;
        gap: .2rem;
        font-size: .82rem;
        color: var(--tt-text);
    }

    .tt-course-actions {
        display: flex;
        justify-content: center;
        gap: .6rem;
        margin-top: .6rem;
        padding-top: .55rem;
        border-top: 1px dashed #ddd3f2;
    }

    .tt-action {
        color: var(--tt-primary);
        font-size: 1rem;
        cursor: pointer;
        text-decoration: none;
    }

    .tt-action:hover {
        color: var(--tt-secondary);
    }

    .tt-action-danger {
        color: var(--tt-danger);
    }


    .tt-course-card-conflict {
    border-left: 5px solid #dc3545 !important;
    background: linear-gradient(180deg, #fff, #fff5f5);
    box-shadow: 0 8px 18px rgba(220, 53, 69, 0.12);
    position: relative;
}

.tt-course-card-conflict::after {
    content: "Conflict";
    position: absolute;
    top: 10px;
    right: 12px;
    font-size: .68rem;
    font-weight: 800;
    color: #dc3545;
    background: #ffe5e8;
    border: 1px solid #f5bcc4;
    border-radius: 999px;
    padding: .15rem .5rem;
}

.tt-conflict-tags {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-top: .45rem;
}

.tt-conflict-tag {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: .18rem .55rem;
    font-size: .68rem;
    font-weight: 700;
    background: #fff1f3;
    color: #b42318;
    border: 1px solid #fecdd3;
}
    .tt-action-danger:hover {
        color: #b02a37;
    }

    .tt-add-inline {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        padding: .45rem .7rem;
        border-radius: 10px;
        background: #f4efff;
        color: var(--tt-primary);
        border: 1px dashed #ccb7ff;
        cursor: pointer;
        font-weight: 600;
        margin-top: .4rem;
    }

    .tt-add-inline:hover {
        background: #ece3ff;
    }

    .tt-empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--tt-muted);
    }

    .tt-empty-state i {
        font-size: 2.5rem;
        margin-bottom: .8rem;
        color: var(--tt-secondary);
    }
</style>

<div class="tt-card">
    <div class="tt-card-header">
        <span>
            <i class="fas fa-table me-2"></i>
            {{ $facultyId ? ($faculties[$facultyId] ?? 'Faculty Timetable') : 'Timetable Overview' }}
        </span>
    </div>

    <div class="tt-table-wrap">
        @if(!$facultyId)
        <div class="tt-empty-state">
            <i class="fas fa-building"></i>
            <div class="fw-bold mb-2">Select a faculty to view timetable entries</div>
            <div>Use the faculty filter above to load the timetable grid.</div>
        </div>
        @else
        <table class="tt-table">
            <thead>
                <tr>
                    <th>Time</th>
                    @foreach($days as $day)
                    <th>{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                $activitiesByDay = $timetables->groupBy('day')->map(function ($group) {
                return $group->sortBy('time_start');
                });
                $occupiedUntil = array_fill_keys($days, -1);
                @endphp

                @foreach($timeSlots as $i => $slotStart)
                <tr>
                    <td class="tt-time-cell">
                        {{ $slotStart }} - {{ date('H:i', strtotime($slotStart) + 3600) }}
                    </td>

                    @foreach($days as $day)
                    @if($i > $occupiedUntil[$day])
                    @php
                    $activitiesForDay = $activitiesByDay->get($day, collect());
                    $activities = $activitiesForDay->filter(function ($act) use ($slotStart) {
                    return substr($act->time_start, 0, 5) === $slotStart;
                    });

                    $maxDuration = $activities->max(function ($act) {
                    return max(1, (strtotime($act->time_end) - strtotime($act->time_start)) / 3600);
                    }) ?? 1;

                    $rowspan = ceil($maxDuration);
                    $occupiedUntil[$day] = $i + $rowspan - 1;

                    $activityBadgeClass = function ($activity) {
                    return match(strtolower($activity ?? 'lecture')) {
                    'practical' => 'tt-badge tt-badge-practical',
                    'workshop' => 'tt-badge tt-badge-workshop',
                    default => 'tt-badge tt-badge-lecture',
                    };
                    };
                    @endphp

                    <td rowspan="{{ $rowspan }}">
                        @if($activities->isNotEmpty())
                        <div class="tt-slot-stack">
                            @foreach($activities as $activity)
                            @php
                            $venueNames = collect(explode(',', (string) $activity->venue_id))
                            ->map(fn($id) => trim($id))
                            ->filter()
                            ->map(function ($id) use ($venueMap) {
                            return $venueMap->has((int)$id)
                            ? $venueMap[(int)$id]->name
                            : "Venue {$id}";
                            })
                            ->implode(', ');

                            $isCross = false;
                            if (isset($activity->course) && $activity->course) {
                            $isCross = (bool) $activity->course->cross_catering;
                            }
                            @endphp

                            @php
                            $hasLecturerCollision = $timetables->contains(function ($row) use ($activity) {
                            return $row->id !== $activity->id
                            && $row->semester_id == $activity->semester_id
                            && $row->day === $activity->day
                            && $row->lecturer_id == $activity->lecturer_id
                            && $row->time_start < $activity->time_end
                                && $row->time_end > $activity->time_start;
                                });

                                $hasFacultyCollision = $timetables->contains(function ($row) use ($activity) {
                                return $row->id !== $activity->id
                                && $row->semester_id == $activity->semester_id
                                && $row->day === $activity->day
                                && $row->faculty_id == $activity->faculty_id
                                && $row->time_start < $activity->time_end
                                    && $row->time_end > $activity->time_start;
                                    });

                                    $currentGroups = $activity->group_selection === 'All Groups'
                                    ? \App\Models\FacultyGroup::where('faculty_id',
                                    $activity->faculty_id)->pluck('group_name')->toArray()
                                    : array_map('trim', explode(',', $activity->group_selection));

                                    $activityVenueIds = collect(explode(',', (string) $activity->venue_id))
                                    ->map(fn($id) => (int) trim($id))
                                    ->filter()
                                    ->values()
                                    ->all();

                                    $hasGroupCollision = $timetables->contains(function ($row) use ($activity,
                                    $currentGroups) {
                                    if (
                                    $row->id === $activity->id ||
                                    $row->semester_id != $activity->semester_id ||
                                    $row->day !== $activity->day ||
                                    $row->faculty_id != $activity->faculty_id ||
                                    !($row->time_start < $activity->time_end && $row->time_end > $activity->time_start)
                                        ) {
                                        return false;
                                        }

                                        $rowGroups = $row->group_selection === 'All Groups'
                                        ? \App\Models\FacultyGroup::where('faculty_id',
                                        $row->faculty_id)->pluck('group_name')->toArray()
                                        : array_map('trim', explode(',', $row->group_selection));

                                        return count(array_intersect($currentGroups, $rowGroups)) > 0;
                                        });

                                        $hasVenueCollision = $timetables->contains(function ($row) use ($activity,
                                        $activityVenueIds) {
                                        if (
                                        $row->id === $activity->id ||
                                        $row->semester_id != $activity->semester_id ||
                                        $row->day !== $activity->day ||
                                        !($row->time_start < $activity->time_end && $row->time_end >
                                            $activity->time_start)
                                            ) {
                                            return false;
                                            }

                                            $rowVenueIds = collect(explode(',', (string) $row->venue_id))
                                            ->map(fn($id) => (int) trim($id))
                                            ->filter()
                                            ->values()
                                            ->all();

                                            return count(array_intersect($activityVenueIds, $rowVenueIds)) > 0;
                                            });

                                            $conflictTypes = collect([
                                            $hasLecturerCollision ? 'lecturer' : null,
                                            $hasFacultyCollision ? 'faculty' : null,
                                            $hasGroupCollision ? 'group' : null,
                                            $hasVenueCollision ? 'venue' : null,
                                            ])->filter()->values()->all();

                                            $isWorkshop = strtolower((string)($activity->activity ?? '')) === 'workshop';
                                            $isSharedCross = $isCross && !$isWorkshop;
                                            $hasAnyCollision = !$isWorkshop && !empty($conflictTypes);
                                            @endphp

                                            <div class="tt-course-card {{ $hasAnyCollision ? 'tt-course-card-conflict' : '' }}"
                                                data-id="{{ $activity->id }}"
                                                data-conflict-types="{{ implode(',', $conflictTypes) }}">
                                                <div class="tt-course-top">
                                                    <div>
                                                        <div class="tt-course-code">{{ $activity->course_code }}</div>
                                                        <div class="tt-inline-note">{{ $activity->course_name ??
                                                            ($activity->course->name ?? 'N/A') }}</div>
                                                    </div>
                                                    <span class="{{ $activityBadgeClass($activity->activity) }}">
                                                        {{ $activity->activity ?? 'Lecture' }}
                                                    </span>
                                                </div>

                                                <div class="tt-course-meta">
                                                    <div><strong>Groups:</strong> {{ $activity->group_selection }}</div>
                                                    <div><strong>Venue:</strong> {{ $venueNames ?: 'N/A' }}</div>
                                                    <div><strong>Assigned User:</strong> {{ $activity->lecturer->name ??
                                                        'N/A' }}</div>
                                                    @if($isCross)
                                                    <div><strong>Mode:</strong> Cross-catering</div>
                                                    @endif

                                                    @if($hasAnyCollision)
                                                        <div class="tt-conflict-tags">
                                                            @foreach($conflictTypes as $type)
                                                                <span class="tt-conflict-tag">
                                                                    {{ ucfirst($type) }} collision
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="tt-course-actions">
                                                    <a href="javascript:void(0)" class="tt-action show-timetable"
                                                        data-id="{{ $activity->id }}" title="View Details">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>

                                                    <a href="javascript:void(0)" class="tt-action edit-timetable"
                                                        data-id="{{ $activity->id }}" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>

                                                    <form action="{{ route('timetable.destroy', $activity->id) }}"
                                                        method="POST"
                                                        class="delete-timetable-form d-inline"
                                                        data-cross="{{ $isCross ? 1 : 0 }}"
                                                        data-workshop="{{ strtolower((string)($activity->activity ?? '')) === 'workshop' ? 1 : 0 }}">

                                                        <button type="submit"
                                                            class="tt-action tt-action-danger border-0 bg-transparent p-0"
                                                            title="Delete">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            @endforeach

                                            <div class="tt-add-inline add-timetable" data-day="{{ $day }}"
                                                data-time="{{ $slotStart }}" data-faculty="{{ $facultyId }}"
                                                data-setup="{{ $timetableSemester?->id }}">
                                                <i class="bi bi-plus-circle"></i>
                                                Add
                                            </div>
                        </div>
                        @else
                        <div class="tt-empty-cell add-timetable" data-day="{{ $day }}" data-time="{{ $slotStart }}"
                            data-faculty="{{ $facultyId }}" data-setup="{{ $timetableSemester?->id }}">
                            <div>
                                <i class="bi bi-plus-circle fs-4 d-block mb-2"></i>
                                <div>Add Session</div>
                            </div>
                        </div>
                        @endif
                    </td>
                    @endif
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

</div>