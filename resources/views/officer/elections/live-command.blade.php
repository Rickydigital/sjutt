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
let topCandidatesChart = null;
let facultyChart = null;
let programChart = null;
let positionCharts = {};

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

function chartColors() {
    return [
        '#1572e8',
        '#31ce36',
        '#f25961',
        '#ffad46',
        '#6861ce',
        '#48abf7',
        '#2bb930',
        '#fd7e14'
    ];
}

function makeOrUpdateChart(instance, canvasId, type, labels, data, label) {
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
                backgroundColor: chartColors(),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            animation: false,
            plugins: {
                legend: {
                    position: type === 'doughnut' ? 'bottom' : 'top'
                }
            },
            scales: type === 'bar' ? {
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

function renderPositionCards(positions) {
    let html = '';

    positions.forEach(position => {
        html += `
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-1">${safe(position.name)}</h5>
                        <span class="badge bg-primary">${safe(position.scope.toUpperCase())}</span>
                    </div>

                    <div class="text-muted small">
                        Voters: <strong>${nf(position.voters)}</strong>
                        • Votes: <strong>${nf(position.total_votes)}</strong>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-xl-5">
                            <canvas id="positionChart${position.id}" height="260"></canvas>
                        </div>

                        <div class="col-xl-7">
                            ${renderCandidateBars(position)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    document.getElementById('positionsContainer').innerHTML = html;

    positions.forEach(position => {
        const labels = position.candidates.map(c => c.name);
        const votes = position.candidates.map(c => c.votes);

        positionCharts[position.id] = makeOrUpdateChart(
            positionCharts[position.id],
            'positionChart' + position.id,
            'bar',
            labels,
            votes,
            'Votes'
        );
    });
}

function renderCandidateBars(position) {
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

function getTopCandidates(positions) {
    let all = [];

    positions.forEach(position => {
        position.candidates.forEach(candidate => {
            all.push({
                name: candidate.name + ' - ' + position.name,
                votes: candidate.votes
            });
        });
    });

    return all.sort((a, b) => b.votes - a.votes).slice(0, 8);
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

            turnoutDonut = makeOrUpdateChart(
                turnoutDonut,
                'turnoutDonut',
                'doughnut',
                ['Voted', 'Not Voted'],
                [data.summary.unique_voters, data.summary.not_voted],
                'Students'
            );

            scopeDonut = makeOrUpdateChart(
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

            const top = getTopCandidates(data.positions);

            topCandidatesChart = makeOrUpdateChart(
                topCandidatesChart,
                'topCandidatesChart',
                'bar',
                top.map(x => x.name),
                top.map(x => x.votes),
                'Votes'
            );

            facultyChart = makeOrUpdateChart(
                facultyChart,
                'facultyChart',
                'bar',
                data.faculty_turnout.map(x => x.name),
                data.faculty_turnout.map(x => x.turnout),
                'Turnout %'
            );

            programChart = makeOrUpdateChart(
                programChart,
                'programChart',
                'bar',
                data.program_turnout.map(x => x.name),
                data.program_turnout.map(x => x.turnout),
                'Turnout %'
            );

            renderPositionCards(data.positions);
            updateCountdown();
        })
        .catch(error => console.log(error));
}

loadLiveCommandCenter();

setInterval(loadLiveCommandCenter, 3000);
setInterval(updateCountdown, 1000);
</script>
@endsection