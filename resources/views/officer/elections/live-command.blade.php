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

    {{-- TOP METRICS --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Eligible Students</div>
                    <h2 id="eligibleStudents" class="fw-bold mb-0">0</h2>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Students Voted</div>
                    <h2 id="uniqueVoters" class="fw-bold mb-0">0</h2>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Ballots Cast</div>
                    <h2 id="totalVotes" class="fw-bold mb-0">0</h2>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-dark text-white">
                <div class="card-body">
                    <div class="small">Time Remaining</div>
                    <h2 id="countdown" class="fw-bold mb-0">--:--:--</h2>
                </div>
            </div>
        </div>
    </div>

    {{-- CHARTS SUMMARY --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Overall Turnout</div>
                <div class="card-body">
                    <canvas id="turnoutDonut" height="220"></canvas>
                    <div class="text-center mt-3">
                        <h3><span id="turnoutPercent">0</span>%</h3>
                        <small class="text-muted">Live voter participation</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Scope Distribution</div>
                <div class="card-body">
                    <canvas id="scopeDonut" height="220"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Top Live Candidates</div>
                <div class="card-body">
                    <canvas id="topCandidatesChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- FACULTY AND PROGRAM BREAKDOWN --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Faculty Turnout Breakdown</div>
                <div class="card-body">
                    <canvas id="facultyChart" height="260"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Program Turnout Breakdown</div>
                <div class="card-body">
                    <canvas id="programChart" height="260"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- POSITION DETAILS --}}
    <div id="positionsContainer"></div>

</div>
@endsection

@section('scripts')
<script>
let closeAt = null;

let turnoutDonut = null;
let scopeDonut = null;
let programTurnoutChart = null;
let facultyTurnoutChart = null;
let globalCharts = {};
let programCharts = {};
let facultyCharts = {};

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

function colors() {
    return [
        '#1572e8',
        '#31ce36',
        '#f25961',
        '#ffad46',
        '#6861ce',
        '#48abf7',
        '#2bb930',
        '#fd7e14',
        '#20c997',
        '#6610f2'
    ];
}

function chart(instance, canvasId, type, labels, data, label, horizontal = false) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    if (instance) {
        instance.data.labels = labels;
        instance.data.datasets[0].data = data;
        instance.update();
        return instance;
    }

    return new Chart(ctx, {
        type: type,
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: data,
                backgroundColor: colors(),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            animation: false,
            plugins: {
                legend: {
                    position: type === 'doughnut' ? 'bottom' : 'top'
                }
            },
            scales: type === 'bar' ? {
                x: {
                    beginAtZero: true
                },
                y: {
                    beginAtZero: true
                }
            } : {}
        }
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

function candidateList(position) {
    if (!position.candidates || position.candidates.length === 0) {
        return `<div class="alert alert-warning mb-0">No candidates found.</div>`;
    }

    let html = '';

    position.candidates.forEach(candidate => {
        html += `
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <strong>#${candidate.rank} ${safe(candidate.name)}</strong><br>
                        <small class="text-muted">
                            ${safe(candidate.reg_no ?? '')}
                            ${candidate.program ? ' • ' + safe(candidate.program) : ''}
                            ${candidate.faculty ? ' • ' + safe(candidate.faculty) : ''}
                        </small>
                    </div>

                    <div class="text-end">
                        <strong>${nf(candidate.votes)} votes</strong><br>
                        <small class="text-muted">${candidate.percent}%</small>
                    </div>
                </div>

                <div class="progress mt-2" style="height: 12px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         style="width: ${candidate.percent}%">
                    </div>
                </div>
            </div>
        `;
    });

    return html;
}

function renderGlobalPositions(globalPositions) {
    let html = `
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-globe"></i> Global Position Charts
                </h4>
                <small class="text-muted">All global positions accumulated alone.</small>
            </div>
            <div class="card-body">
    `;

    if (!globalPositions || globalPositions.length === 0) {
        html += `<div class="alert alert-info mb-0">No global positions found.</div>`;
    } else {
        globalPositions.forEach(position => {
            html += `
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">${safe(position.name)}</h5>
                            <span class="badge bg-success">GLOBAL</span>
                        </div>
                        <div class="text-muted">
                            Total votes: <strong>${nf(position.total_votes)}</strong>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-xl-5">
                            <canvas id="globalChart${position.id}" height="260"></canvas>
                        </div>
                        <div class="col-xl-7">
                            ${candidateList(position)}
                        </div>
                    </div>
                </div>
            `;
        });
    }

    html += `</div></div>`;

    return html;
}

function renderProgramGroups(programGroups) {
    let html = `
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-diagram-3"></i> Program Position Charts
                </h4>
                <small class="text-muted">Each program is separated with its own candidates and charts.</small>
            </div>
            <div class="card-body">
    `;

    if (!programGroups || programGroups.length === 0) {
        html += `<div class="alert alert-info mb-0">No program positions found.</div>`;
    } else {
        programGroups.forEach(program => {
            html += `
                <div class="border rounded p-3 mb-4 bg-light">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-mortarboard"></i> ${safe(program.name)}
                    </h5>
            `;

            program.positions.forEach(position => {
                html += `
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <strong>${safe(position.name)}</strong>
                                <span class="badge bg-info text-dark ms-1">PROGRAM</span>
                            </div>
                            <span class="text-muted">Votes: <strong>${nf(position.total_votes)}</strong></span>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-xl-5">
                                    <canvas id="programChart${program.id}_${position.id}" height="260"></canvas>
                                </div>
                                <div class="col-xl-7">
                                    ${candidateList(position)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
        });
    }

    html += `</div></div>`;

    return html;
}

function renderFacultyGroups(facultyGroups) {
    let html = `
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-buildings"></i> Faculty Position Charts
                </h4>
                <small class="text-muted">Each faculty is separated with its own candidates and charts.</small>
            </div>
            <div class="card-body">
    `;

    if (!facultyGroups || facultyGroups.length === 0) {
        html += `<div class="alert alert-info mb-0">No faculty positions found.</div>`;
    } else {
        facultyGroups.forEach(faculty => {
            html += `
                <div class="border rounded p-3 mb-4 bg-light">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-building"></i> ${safe(faculty.name)}
                    </h5>
            `;

            faculty.positions.forEach(position => {
                html += `
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <strong>${safe(position.name)}</strong>
                                <span class="badge bg-primary ms-1">FACULTY</span>
                            </div>
                            <span class="text-muted">Votes: <strong>${nf(position.total_votes)}</strong></span>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-xl-5">
                                    <canvas id="facultyChart${faculty.id}_${position.id}" height="260"></canvas>
                                </div>
                                <div class="col-xl-7">
                                    ${candidateList(position)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
        });
    }

    html += `</div></div>`;

    return html;
}

function drawGlobalCharts(globalPositions) {
    globalPositions.forEach(position => {
        globalCharts[position.id] = chart(
            globalCharts[position.id],
            'globalChart' + position.id,
            'bar',
            position.candidates.map(c => c.name),
            position.candidates.map(c => c.votes),
            'Votes',
            true
        );
    });
}

function drawProgramCharts(programGroups) {
    programGroups.forEach(program => {
        program.positions.forEach(position => {
            const key = program.id + '_' + position.id;

            programCharts[key] = chart(
                programCharts[key],
                'programChart' + key,
                'bar',
                position.candidates.map(c => c.name),
                position.candidates.map(c => c.votes),
                'Votes',
                true
            );
        });
    });
}

function drawFacultyCharts(facultyGroups) {
    facultyGroups.forEach(faculty => {
        faculty.positions.forEach(position => {
            const key = faculty.id + '_' + position.id;

            facultyCharts[key] = chart(
                facultyCharts[key],
                'facultyChart' + key,
                'bar',
                position.candidates.map(c => c.name),
                position.candidates.map(c => c.votes),
                'Votes',
                true
            );
        });
    });
}

function renderAllSections(data) {
    let html = '';

    html += renderGlobalPositions(data.global_positions);
    html += renderProgramGroups(data.program_positions);
    html += renderFacultyGroups(data.faculty_positions);

    document.getElementById('positionsContainer').innerHTML = html;

    drawGlobalCharts(data.global_positions);
    drawProgramCharts(data.program_positions);
    drawFacultyCharts(data.faculty_positions);
}

function loadLiveCommandCenter() {
    fetch("{{ route('officer.elections.live-command.data', $election) }}")
        .then(response => response.json())
        .then(data => {
            closeAt = data.election.close_at;

            document.getElementById('eligibleStudents').innerText = nf(data.summary.eligible);
            document.getElementById('uniqueVoters').innerText = nf(data.summary.unique_voters);
            document.getElementById('totalVotes').innerText = nf(data.summary.total_votes);
            document.getElementById('turnoutPercent').innerText = data.summary.turnout;
            document.getElementById('updatedAt').innerText = data.updated_at;

            turnoutDonut = chart(
                turnoutDonut,
                'turnoutDonut',
                'doughnut',
                ['Voted', 'Not Voted'],
                [data.summary.unique_voters, data.summary.not_voted],
                'Students'
            );

            scopeDonut = chart(
                scopeDonut,
                'scopeDonut',
                'doughnut',
                ['Global', 'Faculty', 'Program'],
                [
                    data.scope_summary.global,
                    data.scope_summary.faculty,
                    data.scope_summary.program
                ],
                'Positions'
            );

            programTurnoutChart = chart(
                programTurnoutChart,
                'programChart',
                'bar',
                data.program_turnout.map(x => x.name),
                data.program_turnout.map(x => x.turnout),
                'Turnout %',
                true
            );

            facultyTurnoutChart = chart(
                facultyTurnoutChart,
                'facultyChart',
                'bar',
                data.faculty_turnout.map(x => x.name),
                data.faculty_turnout.map(x => x.turnout),
                'Turnout %',
                true
            );

            renderAllSections(data);
            updateCountdown();
        })
        .catch(error => console.log(error));
}

loadLiveCommandCenter();

setInterval(loadLiveCommandCenter, 3000);
setInterval(updateCountdown, 1000);
</script>
@endsection