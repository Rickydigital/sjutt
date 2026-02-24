@php
// Helper functions (unchanged)
function initials($first, $last) {
$a = mb_substr((string)$first, 0, 1);
$b = mb_substr((string)$last, 0, 1);
$out = trim(($a . $b));
return $out !== '' ? mb_strtoupper($out) : 'NA';
}

function scopeLabel($scope) {
return match($scope) {
'global' => 'GLOBAL',
'program' => 'PROGRAM',
'faculty' => 'FACULTY',
default => strtoupper($scope ?? 'OTHER'),
};
}

function scopeBadge($scope) {
return match($scope) {
'global' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
'program' => 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200',
'faculty' => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200',
default => 'bg-gray-100 text-gray-700 ring-1 ring-gray-200',
};
}

// Calculate total remaining positions across ALL elections
$totalToVote = $elections->sum(fn($election) => $election->positions->count());
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Voting - Open Elections</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .hidden-voter {
            display: none !important;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen pb-20">

    <header class="bg-white border-b border-gray-200 sticky top-0 z-30 py-4 shadow-sm">
        <div class="max-w-4xl mx-auto px-6 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Student Voting</h1>
                <p class="text-sm text-gray-500">Secure Digital Ballot</p>
            </div>

            <div class="flex items-center gap-6">
                <!-- Progress (moved a bit left to make space) -->
                <div class="text-right hidden sm:block">
                    <span id="progress-text" class="text-xs font-bold text-indigo-600 uppercase">
                        0 of {{ $totalToVote }} Cast
                    </span>
                    <div class="w-40 bg-gray-200 rounded-full h-2 mt-1">
                        <div id="progress-bar" class="bg-indigo-600 h-2 rounded-full transition-all duration-500"
                            style="width:0%"></div>
                    </div>
                </div>

                <!-- Logout button -->
                <form method="POST" action="{{ route('stu.logout') }}"
                    onsubmit="return confirm('Are you sure you want to logout?')">
                    @csrf
                    <button type="submit"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors text-sm border border-gray-300">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto mt-8 px-6 space-y-12">

        @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
            <div class="font-bold mb-1">Fix the following:</div>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if ($elections->isEmpty() || $totalToVote === 0)
        <div class="bg-white border border-gray-200 rounded-2xl p-10 text-center shadow-sm">
            <div class="text-5xl mb-4">✅</div>
            <h2 class="text-2xl font-bold text-gray-900">Nothing to vote on right now</h2>
            <p class="text-gray-500 mt-2">
                There are no open elections, or you've already voted for all available positions.
            </p>
        </div>
        @else

        @foreach ($elections as $election)
        <section class="space-y-6">
            <!-- Election Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $election->title }}</h2>
                    @if($election->description)
                    <p class="text-sm text-gray-600 mt-1">{{ $election->description }}</p>
                    @endif
                </div>
                <div class="text-sm font-medium text-gray-500 whitespace-nowrap">
                    {{ $election->positions->count() }} position(s) available
                </div>
            </div>

            @php
            // Sort positions: general → program → faculty
            $priority = ['global' => 1, 'program' => 2, 'faculty' => 3];
            $sorted = $election->positions->sortBy(fn($p) => $priority[$p->scope_type] ?? 99);
            $grouped = $sorted->groupBy('scope_type');
            @endphp

            @foreach (['global', 'program', 'faculty'] as $scopeType)
            @php $group = $grouped->get($scopeType, collect()); @endphp
            @if ($group->isNotEmpty())
            <div class="mt-6">
                <div class="flex items-end justify-between mb-3">
                    <h3 class="text-sm font-extrabold tracking-widest text-gray-700 uppercase">
                        {{ scopeLabel($scopeType) }} Positions
                    </h3>
                    <span class="text-xs font-semibold text-gray-500">
                        {{ $group->count() }} position(s)
                    </span>
                </div>

                <div class="grid gap-5">
                    @foreach ($group as $position)
                    @include('partials.vote_position_card', ['position' => $position])
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach
        </section>
        @endforeach

        @endif
    </main>

    <!-- Confirm Modal -->
    <div id="modal-overlay"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div
            class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl animate__animated animate__zoomIn animate__faster">
            <div
                class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4 mx-auto text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h3 class="text-xl font-bold text-center text-gray-900">Confirm Vote</h3>
            <p id="modal-text" class="text-center text-gray-500 mt-2 italic"></p>

            <div class="mt-8 flex gap-3">
                <button type="button" onclick="closeModal()"
                    class="flex-1 py-3 border border-gray-200 rounded-xl text-gray-600 font-medium hover:bg-gray-50 transition-colors">
                    Cancel
                </button>

                <button type="button" onclick="processVote()"
                    class="flex-1 py-3 bg-indigo-600 rounded-xl text-white font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200">
                    Yes, Cast
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden vote submission form -->
    <form id="voteForm" method="POST" action="{{ route('student.vote.store') }}" class="hidden">
        @csrf
        <input type="hidden" name="election_position_id" id="vote_position_id">
        <input type="hidden" name="candidate_id" id="vote_candidate_id">
    </form>

    <script>
        let votedCount = 0;
    const totalCount = {{ $totalToVote }};
    let pendingVote = { positionId: null, candidateId: null, candidateName: '' };

    function openModal(positionId, candidateId, candidateName) {
        pendingVote = { positionId, candidateId, candidateName };
        document.getElementById('modal-text').innerText = `Confirming your vote for ${candidateName}`;
        const modal = document.getElementById('modal-overlay');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('modal-overlay');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function processVote() {
        votedCount++;
        const percent = totalCount ? (votedCount / totalCount) * 100 : 0;
        document.getElementById('progress-bar').style.width = `${percent}%`;
        document.getElementById('progress-text').innerText = `${votedCount} of ${totalCount} Cast`;

        // Hide voting buttons + show confirmed message for this position
        const actionBox = document.getElementById(`actions-${pendingVote.positionId}`);
        const confirmed = document.getElementById(`confirmed-${pendingVote.positionId}`);
        if (actionBox) actionBox.classList.add('hidden-voter');
        if (confirmed) confirmed.classList.remove('hidden');

        closeModal();

        // Submit vote
        document.getElementById('vote_position_id').value = pendingVote.positionId;
        document.getElementById('vote_candidate_id').value = pendingVote.candidateId;
        document.getElementById('voteForm').submit();
    }
    </script>

</body>

</html>