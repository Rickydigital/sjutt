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
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small">Total Votes</div>
                <h2 id="totalVotes" class="fw-bold mb-0">0</h2>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small">Students Voted</div>
                <h2 id="uniqueVoters" class="fw-bold mb-0">0</h2>
                <small><span id="turnoutPercent">0</span>% turnout</small>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small">Remaining Eligible</div>
                <h2 id="remainingStudents" class="fw-bold mb-0">0</h2>
                <small><span id="remainingPercent">0</span>% not voted</small>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-dark text-white"><div class="card-body">
                <div class="small">Time Remaining</div>
                <h2 id="countdown" class="fw-bold mb-0">--:--:--</h2>
                <small><span id="timePercent">0</span>% time left</small>
            </div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Programs Performance - Top to Least</div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Program</th><th>Eligible</th><th>Voted</th><th>Remaining</th><th>Percent</th>
                    </tr>
                </thead>
                <tbody id="programTable"></tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Faculty Performance - Top to Least</div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Faculty</th><th>Eligible</th><th>Voted</th><th>Remaining</th><th>Percent</th>
                    </tr>
                </thead>
                <tbody id="facultyTable"></tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Election Progress Summary</div>
        <div class="card-body" id="progressSummary"></div>
    </div>

    <div id="candidateSections"></div>

</div>
@endsection

@section('scripts')
<script>
let closeAt = null;

function nf(v) {
    return new Intl.NumberFormat().format(v ?? 0);
}

function safe(text) {
    return String(text ?? '').replace(/[&<>"']/g, m => ({
        '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'
    })[m]);
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
        html = `<tr><td colspan="6" class="text-center text-muted">No data found.</td></tr>`;
    } else {
        rows.forEach((row, i) => {
            html += `
                <tr>
                    <td><span class="badge bg-${i === 0 ? 'success' : i === 1 ? 'primary' : i === 2 ? 'info' : 'secondary'}">#${i + 1}</span></td>
                    <td class="fw-semibold">${safe(row.name)}</td>
                    <td>${nf(row.eligible)}</td>
                    <td>${nf(row.voted)}</td>
                    <td>${nf(row.remaining)}</td>
                    <td>
                        <strong>${row.percent}%</strong>
                        <div class="progress mt-1" style="height:8px;">
                            <div class="progress-bar" style="width:${row.percent}%"></div>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    document.getElementById(targetId).innerHTML = html;
}

function renderProgress(progress) {
    document.getElementById('progressSummary').innerHTML = `
        <div class="row g-3">
            ${box('Status', progress.status)}
            ${box('Positions', nf(progress.positions))}
            ${box('Candidates', nf(progress.candidates))}
            ${box('Global Positions', nf(progress.global_positions))}
            ${box('Program Positions', nf(progress.program_positions))}
            ${box('Faculty Positions', nf(progress.faculty_positions))}
        </div>
    `;
}

function box(label, value) {
    return `
        <div class="col-md-2">
            <div class="border rounded p-3 h-100">
                <div class="text-muted small">${label}</div>
                <h5 class="fw-bold mb-0">${value}</h5>
            </div>
        </div>
    `;
}

function candidateList(position) {
    if (!position.candidates || position.candidates.length === 0) {
        return `<div class="alert alert-warning mb-0">No candidates found.</div>`;
    }

    let html = '';

    position.candidates.forEach(c => {
        html += `
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <strong>#${c.rank} ${safe(c.name)}</strong><br>
                        <small class="text-muted">
                            ${safe(c.reg_no ?? '')}
                            ${c.program ? ' • ' + safe(c.program) : ''}
                            ${c.faculty ? ' • ' + safe(c.faculty) : ''}
                        </small>
                    </div>
                    <div class="text-end">
                        <strong>${nf(c.votes)} votes</strong><br>
                        <small>${c.percent}%</small>
                    </div>
                </div>

                <div class="progress mt-2" style="height:12px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         style="width:${c.percent}%"></div>
                </div>
            </div>
        `;
    });

    return html;
}

function renderPosition(position, badge) {
    return `
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <strong>${safe(position.name)}</strong>
                    <span class="badge ${badge} ms-1">${safe(position.scope.toUpperCase())}</span>
                </div>
                <span class="text-muted">Votes: <strong>${nf(position.total_votes)}</strong></span>
            </div>
            <div class="card-body">
                ${candidateList(position)}
            </div>
        </div>
    `;
}

function renderCandidateSections(data) {
    let html = '';

    html += `
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Global Position Candidates</div>
            <div class="card-body">
                ${data.global_positions.length ? data.global_positions.map(p => renderPosition(p, 'bg-success')).join('') : '<div class="alert alert-info mb-0">No global positions.</div>'}
            </div>
        </div>
    `;

    html += `
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Program Candidates Grouped by Program</div>
            <div class="card-body">
                ${data.program_positions.length ? data.program_positions.map(program => `
                    <div class="border rounded p-3 mb-4 bg-light">
                        <h5 class="fw-bold mb-3">${safe(program.name)}</h5>
                        ${program.positions.map(p => renderPosition(p, 'bg-info text-dark')).join('')}
                    </div>
                `).join('') : '<div class="alert alert-info mb-0">No program positions.</div>'}
            </div>
        </div>
    `;

    html += `
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Faculty Candidates Grouped by Faculty</div>
            <div class="card-body">
                ${data.faculty_positions.length ? data.faculty_positions.map(faculty => `
                    <div class="border rounded p-3 mb-4 bg-light">
                        <h5 class="fw-bold mb-3">${safe(faculty.name)}</h5>
                        ${faculty.positions.map(p => renderPosition(p, 'bg-primary')).join('')}
                    </div>
                `).join('') : '<div class="alert alert-info mb-0">No faculty positions.</div>'}
            </div>
        </div>
    `;

    document.getElementById('candidateSections').innerHTML = html;
}

function loadLiveCommandCenter() {
    fetch("{{ route('officer.elections.live-command.data', $election) }}")
        .then(res => res.json())
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
            renderProgress(data.election_progress);
            renderCandidateSections(data);

            updateCountdown();
        })
        .catch(e => console.log(e));
}

loadLiveCommandCenter();
setInterval(loadLiveCommandCenter, 3000);
setInterval(updateCountdown, 1000);
</script>
@endsection