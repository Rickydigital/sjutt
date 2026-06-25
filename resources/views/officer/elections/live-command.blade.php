@extends('officer.layouts.app')

@section('title', 'Live Election Command Center')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">{{ $election->title }}</h3>
            <span class="badge bg-success">LIVE COMMAND CENTER</span>
            <span class="text-muted ms-2">Updated: <span id="updatedAt">--:--:--</span></span>
        </div>

        <a href="{{ route('officer.elections.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Votes</div>
                    <h2 id="totalVotes" class="fw-bold mb-0">0</h2>
                    <small class="text-muted">All ballots cast</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Students Voted</div>
                    <h2 id="uniqueVoters" class="fw-bold mb-0">0</h2>
                    <small class="text-muted"><span id="turnoutPercent">0</span>% turnout</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Remaining Eligible</div>
                    <h2 id="remainingStudents" class="fw-bold mb-0">0</h2>
                    <small class="text-muted"><span id="remainingPercent">0</span>% not voted</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-dark text-white">
                <div class="card-body">
                    <div class="small">Time Remaining</div>
                    <h2 id="countdown" class="fw-bold mb-0">--:--:--</h2>
                    <small><span id="timePercent">0</span>% time left</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            Programs Performance - Top to Least
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Program</th>
                        <th>Eligible</th>
                        <th>Voted</th>
                        <th>Remaining</th>
                        <th style="width: 220px;">Percent</th>
                    </tr>
                </thead>
                <tbody id="programTable">
                    <tr>
                        <td colspan="6" class="text-center text-muted">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            Faculty Performance - Top to Least
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Faculty</th>
                        <th>Eligible</th>
                        <th>Voted</th>
                        <th>Remaining</th>
                        <th style="width: 220px;">Percent</th>
                    </tr>
                </thead>
                <tbody id="facultyTable">
                    <tr>
                        <td colspan="6" class="text-center text-muted">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            Election Progress Summary
        </div>
        <div class="card-body">
            <div id="progressSummary"></div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
let closeAt = null;

function nf(value) {
    return new Intl.NumberFormat().format(value ?? 0);
}

function safe(text) {
    return String(text ?? '').replace(/[&<>"']/g, function (m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m];
    });
}

function updateCountdown() {
    if (!closeAt) {
        document.getElementById('countdown').innerText = 'Not set';
        return;
    }

    let diff = new Date(closeAt).getTime() - new Date().getTime();

    if (diff <= 0) {
        document.getElementById('countdown').innerText = 'Closed';
        return;
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    diff -= days * 1000 * 60 * 60 * 24;

    const hours = Math.floor(diff / (1000 * 60 * 60));
    diff -= hours * 1000 * 60 * 60;

    const minutes = Math.floor(diff / (1000 * 60));
    diff -= minutes * 1000 * 60;

    const seconds = Math.floor(diff / 1000);

    document.getElementById('countdown').innerText =
        (days > 0 ? days + 'd ' : '') +
        String(hours).padStart(2, '0') + ':' +
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0');
}

function renderRankTable(rows, targetId) {
    let html = '';

    if (!rows || rows.length === 0) {
        html = `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    No data found.
                </td>
            </tr>
        `;
    } else {
        rows.forEach((row, index) => {
            let badge = 'secondary';

            if (index === 0) badge = 'success';
            else if (index === 1) badge = 'primary';
            else if (index === 2) badge = 'info';

            html += `
                <tr>
                    <td>
                        <span class="badge bg-${badge}">#${index + 1}</span>
                    </td>
                    <td class="fw-semibold">${safe(row.name)}</td>
                    <td>${nf(row.eligible)}</td>
                    <td>${nf(row.voted)}</td>
                    <td>${nf(row.remaining)}</td>
                    <td>
                        <div class="d-flex justify-content-between">
                            <strong>${row.percent}%</strong>
                        </div>
                        <div class="progress mt-1" style="height: 8px;">
                            <div class="progress-bar" style="width: ${row.percent}%"></div>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    document.getElementById(targetId).innerHTML = html;
}

function renderProgressSummary(progress) {
    document.getElementById('progressSummary').innerHTML = `
        <div class="row g-3">
            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Status</div>
                    <h5 class="fw-bold mb-0">${safe(progress.status)}</h5>
                </div>
            </div>

            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Positions</div>
                    <h4 class="fw-bold mb-0">${nf(progress.positions)}</h4>
                </div>
            </div>

            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Candidates</div>
                    <h4 class="fw-bold mb-0">${nf(progress.candidates)}</h4>
                </div>
            </div>

            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Global Positions</div>
                    <h4 class="fw-bold mb-0">${nf(progress.global_positions)}</h4>
                </div>
            </div>

            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Program Positions</div>
                    <h4 class="fw-bold mb-0">${nf(progress.program_positions)}</h4>
                </div>
            </div>

            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Faculty Positions</div>
                    <h4 class="fw-bold mb-0">${nf(progress.faculty_positions)}</h4>
                </div>
            </div>
        </div>
    `;
}

function loadLiveCommandCenter() {
    fetch("{{ route('officer.elections.live-command.data', $election) }}")
        .then(response => response.json())
        .then(data => {
            closeAt = data.election.close_at;

            document.getElementById('totalVotes').innerText = nf(data.summary.total_votes);
            document.getElementById('uniqueVoters').innerText = nf(data.summary.unique_voters);
            document.getElementById('remainingStudents').innerText = nf(data.summary.remaining);
            document.getElementById('turnoutPercent').innerText = data.summary.turnout;
            document.getElementById('remainingPercent').innerText = data.summary.remaining_percent;
            document.getElementById('timePercent').innerText = data.summary.time_remaining_percent;
            document.getElementById('updatedAt').innerText = data.updated_at;

            renderRankTable(data.program_table, 'programTable');
            renderRankTable(data.faculty_table, 'facultyTable');
            renderProgressSummary(data.election_progress);

            updateCountdown();
        })
        .catch(error => console.log(error));
}

loadLiveCommandCenter();

setInterval(loadLiveCommandCenter, 3000);
setInterval(updateCountdown, 1000);
</script>
@endsection