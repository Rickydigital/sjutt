@php
    function pollingInitials($first, $last) {
        $a = mb_substr((string) $first, 0, 1);
        $b = mb_substr((string) $last, 0, 1);
        $out = trim($a . $b);
        return $out !== '' ? mb_strtoupper($out) : 'NA';
    }

    function pollingScopeLabel($scope) {
        return match($scope) {
            'global'  => 'General Position',
            'program' => 'Program Position',
            'faculty' => 'Class / Faculty Position',
            default   => strtoupper($scope ?? 'OTHER'),
        };
    }

    $totalToVote = $elections->sum(fn ($election) => $election->positions->count());
    $votesCast = (int) ($session->votes_cast ?? 0);
    $totalInSession = $votesCast + $totalToVote;
    $progressPercent = $totalInSession > 0 ? round(($votesCast / $totalInSession) * 100) : 100;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Polling Centre Voting</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');

        body { font-family: 'Inter', sans-serif; }

        .candidate-card { transition: all .18s ease; }
        .candidate-card:hover { transform: translateY(-2px); }

        .candidate-radio:checked + .candidate-box {
            border-color: #4f46e5;
            background: linear-gradient(135deg, #eef2ff, #ffffff);
            box-shadow: 0 16px 40px rgba(79, 70, 229, .18);
        }

        .candidate-radio:checked + .candidate-box .check-dot {
            opacity: 1;
            transform: scale(1);
        }
    </style>
</head>

<body class="min-h-screen bg-slate-100">

<header class="sticky top-0 z-40 border-b border-white/30 bg-white/90 backdrop-blur-xl">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="h-14 w-14 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-200">
                    <span class="text-2xl">🗳️</span>
                </div>

                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">
                        Polling Centre Voting
                    </h1>
                    <p class="text-sm text-slate-500">
                        {{ $centre->name }} • {{ $centre->election->title }}
                    </p>
                </div>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 min-w-[260px]">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Progress</span>
                    <span class="text-xs font-extrabold text-indigo-600">
                        {{ $votesCast }} cast • {{ $totalToVote }} remaining
                    </span>
                </div>

                <div class="h-2.5 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-indigo-600 to-sky-600 text-white shadow-xl mb-6">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute -top-16 -right-16 h-52 w-52 rounded-full bg-white"></div>
            <div class="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-white"></div>
        </div>

        <div class="relative p-6 sm:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <p class="text-indigo-100 text-sm font-semibold uppercase tracking-wider mb-2">
                        Verified Voter
                    </p>

                    <h2 class="text-2xl sm:text-3xl font-extrabold">
                        {{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}
                    </h2>

                    <p class="text-indigo-100 mt-1">
                        {{ $student->reg_no }}
                    </p>

                    <div class="flex flex-wrap gap-2 mt-4">
                        <span class="px-3 py-1 rounded-full bg-white/15 text-sm">
                            Class: {{ $student->faculty->name ?? '—' }}
                        </span>

                        <span class="px-3 py-1 rounded-full bg-white/15 text-sm">
                            Program: {{ $student->program->short_name ?? $student->program->name ?? '—' }}
                        </span>
                    </div>
                </div>

                <div class="h-24 w-24 rounded-3xl bg-white/20 border border-white/30 flex items-center justify-center text-3xl font-black">
                    {{ pollingInitials($student->first_name, $student->last_name) }}
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-700 font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-700">
            <div class="font-bold mb-1">Fix the following:</div>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if($elections->isEmpty() || $totalToVote === 0)
        <div class="rounded-3xl bg-white border border-slate-200 shadow-sm p-8 sm:p-12 text-center">
            <div class="mx-auto h-20 w-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-4xl mb-5">
                ✅
            </div>

            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                Voting Complete
            </h2>

            <p class="text-slate-500 mt-2 max-w-xl mx-auto">
                This student has completed all available positions. The session can now be closed for the next student.
            </p>

            <form method="POST" action="{{ route('polling.public.finish', $token) }}" class="mt-6">
                @csrf
                <button class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-emerald-600 text-white font-extrabold shadow-lg shadow-emerald-200 hover:bg-emerald-700">
                    Finish and Start Next Student
                </button>
            </form>
        </div>
    @else
        <div class="space-y-8">
            @foreach($elections as $election)
                <section>
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-xl font-extrabold text-slate-900">
                                {{ $election->title }}
                            </h3>
                            <p class="text-sm text-slate-500">
                                Choose one candidate for each position.
                            </p>
                        </div>

                        <span class="text-sm font-bold text-slate-500">
                            {{ $election->positions->count() }} position(s) remaining
                        </span>
                    </div>

                    <div class="space-y-6">
                        @foreach($election->positions as $position)
                            <div class="rounded-3xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                                <div class="bg-slate-900 text-white px-5 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <h4 class="text-lg font-extrabold">
                                            {{ $position->definition->name ?? 'Election Position' }}
                                        </h4>
                                        <p class="text-sm text-slate-300">
                                            {{ pollingScopeLabel($position->scope_type) }}
                                        </p>
                                    </div>

                                    <span class="px-3 py-1 rounded-full bg-white/10 text-xs font-bold uppercase">
                                        {{ $position->candidates->count() }} candidate(s)
                                    </span>
                                </div>

                                <div class="p-5 sm:p-6">
                                    @if($position->candidates->isEmpty())
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3">
                                            No approved candidates available for this position.
                                        </div>
                                    @else
                                        <form method="POST"
                                              action="{{ route('polling.public.vote.store', $token) }}"
                                              class="vote-form">
                                            @csrf

                                            <input type="hidden" name="election_position_id" value="{{ $position->id }}">

                                            <div class="grid md:grid-cols-2 gap-4">
                                                @foreach($position->candidates as $candidate)
                                                    @php
                                                        $candidateStudent = $candidate->student;
                                                        $photoUrl = $candidate->photo_url;

                                                        $candidateName = trim(
                                                            ($candidateStudent->first_name ?? '') . ' ' .
                                                            ($candidateStudent->last_name ?? '')
                                                        );
                                                    @endphp

                                                    <label class="candidate-card cursor-pointer">
                                                        <input type="radio"
                                                               name="candidate_id"
                                                               value="{{ $candidate->id }}"
                                                               required
                                                               class="candidate-radio hidden">

                                                        <div class="candidate-box relative h-full rounded-3xl border-2 border-slate-200 bg-white p-4">
                                                            <div class="check-dot absolute top-4 right-4 h-8 w-8 rounded-full bg-indigo-600 text-white flex items-center justify-center opacity-0 scale-75 transition">
                                                                ✓
                                                            </div>

                                                            <div class="flex gap-4">
                                                                @if($photoUrl)
                                                                    <img src="{{ $photoUrl }}"
                                                                         class="h-24 w-24 rounded-2xl object-cover border border-slate-200"
                                                                         alt="Candidate Photo">
                                                                @else
                                                                    <div class="h-24 w-24 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl font-black">
                                                                        {{ pollingInitials($candidateStudent->first_name ?? '', $candidateStudent->last_name ?? '') }}
                                                                    </div>
                                                                @endif

                                                                <div class="flex-1 pe-8">
                                                                    <h5 class="text-lg font-extrabold text-slate-900">
                                                                        {{ $candidateName ?: 'Candidate' }}
                                                                    </h5>

                                                                    <p class="text-sm text-slate-500">
                                                                        {{ $candidateStudent->reg_no ?? '—' }}
                                                                    </p>

                                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                                        <span class="px-2.5 py-1 rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                                                                            {{ $candidateStudent->faculty->name ?? '—' }}
                                                                        </span>

                                                                        <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-xs font-semibold text-indigo-700">
                                                                            {{ $candidateStudent->program->short_name ?? $candidateStudent->program->name ?? '—' }}
                                                                        </span>
                                                                    </div>

                                                                    @if($candidate->description)
                                                                        <p class="mt-3 text-sm text-slate-500">
                                                                            {{ $candidate->description }}
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            @if($candidate->vice)
                                                                @php
                                                                    $vice = $candidate->vice;
                                                                    $viceStudent = $vice->student;
                                                                    $vicePhotoUrl = $vice->photo_url;
                                                                @endphp

                                                                <div class="mt-4 border-t border-slate-100 pt-4">
                                                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                                                                        Running Mate / Vice
                                                                    </p>

                                                                    <div class="flex items-center gap-3">
                                                                        @if($vicePhotoUrl)
                                                                            <img src="{{ $vicePhotoUrl }}"
                                                                                 class="h-14 w-14 rounded-xl object-cover border border-slate-200"
                                                                                 alt="Vice Candidate Photo">
                                                                        @else
                                                                            <div class="h-14 w-14 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-black">
                                                                                {{ pollingInitials($viceStudent->first_name ?? '', $viceStudent->last_name ?? '') }}
                                                                            </div>
                                                                        @endif

                                                                        <div>
                                                                            <div class="font-bold text-slate-900">
                                                                                {{ $viceStudent->first_name ?? '' }} {{ $viceStudent->last_name ?? '' }}
                                                                            </div>
                                                                            <div class="text-xs text-slate-500">
                                                                                {{ $viceStudent->reg_no ?? '—' }}
                                                                            </div>

                                                                            @if($vice->description)
                                                                                <div class="text-xs text-slate-500 mt-1">
                                                                                    {{ $vice->description }}
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>

                                            <button class="mt-5 w-full py-4 rounded-2xl bg-indigo-600 text-white font-extrabold text-lg hover:bg-indigo-700 shadow-lg shadow-indigo-200">
                                                Submit Vote for This Position
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</main>

<script>
    document.querySelectorAll('.vote-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const selected = form.querySelector('input[name="candidate_id"]:checked');

            if (!selected) {
                e.preventDefault();
                alert('Please select a candidate before submitting.');
                return;
            }

            const card = selected.closest('label');
            const name = card ? card.querySelector('h5')?.innerText : 'this candidate';

            if (!confirm(`Confirm your vote for ${name}?`)) {
                e.preventDefault();
            }
        });
    });
</script>

</body>
</html>