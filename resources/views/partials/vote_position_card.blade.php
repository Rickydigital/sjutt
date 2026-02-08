{{-- resources/views/partials/vote_position_card.blade.php --}}

@php
    $scope = $position->scope_type;
    $badge = scopeBadge($scope);
    $label = scopeLabel($scope);

    $title = $position->definition?->name ?? 'Position';
    $desc  = $position->definition?->description ?? null;

    $cands = $position->candidates ?? collect();

    // helper: safe candidate photo url (from ElectionCandidate->photo)
    $candidatePhotoUrl = function ($cand) {
        if (!empty($cand->photo)) {
            return asset('storage/' . ltrim($cand->photo, '/'));
        }
        return null;
    };

    // helper: safe vice photo url (from ElectionViceCandidate->photo)
    $vicePhotoUrl = function ($vice) {
        if (!empty($vice?->photo)) {
            return asset('storage/' . ltrim($vice->photo, '/'));
        }
        return null;
    };
@endphp

<div id="card-{{ $position->id }}" class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">

        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-base sm:text-lg font-bold text-gray-900 truncate">{{ $title }}</h3>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $badge }}">
                    {{ $label }}
                </span>
            </div>

            @if($desc)
                <p class="text-xs text-gray-500 mt-1">{{ $desc }}</p>
            @endif

            <p class="text-xs text-gray-500 mt-1">
                Candidates: <span class="font-semibold text-gray-700">{{ $cands->count() }}</span>
            </p>
        </div>

        <div class="hidden text-emerald-700 font-bold items-center animate__animated animate__fadeInRight" id="confirmed-{{ $position->id }}">
            <div class="inline-flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-3 py-2 rounded-xl">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                Selection Recorded
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3" id="actions-{{ $position->id }}">
        @forelse ($cands as $cand)
            @php
                $s = $cand->student;

                $name = trim(($s?->first_name ?? '').' '.($s?->last_name ?? ''));
                $reg  = $s?->reg_no ?? '—';
                $faculty = $s?->faculty?->name ?? '—';
                $program = $s?->program?->name ?? '—';

                $ini = initials($s?->first_name, $s?->last_name);

                $img = $candidatePhotoUrl($cand);
                $bio = trim((string)($cand->description ?? ''));

                $notApproved = isset($cand->is_approved) && !$cand->is_approved;

                // ───────────────
                // Vice (optional)
                // ───────────────
                $vice = $cand->vice ?? null;
                $vs   = $vice?->student;

                $vName = trim(($vs?->first_name ?? '').' '.($vs?->last_name ?? ''));
                $vReg  = $vs?->reg_no ?? '—';
                $vIni  = initials($vs?->first_name, $vs?->last_name);
                $vImg  = $vicePhotoUrl($vice);
                $vBio  = trim((string)($vice?->description ?? ''));

                $btnClass = $notApproved
                    ? 'text-left w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 opacity-70 cursor-not-allowed'
                    : 'text-left w-full bg-white hover:bg-indigo-50 border border-gray-200 hover:border-indigo-200 rounded-2xl p-4 transition active:scale-[0.99] shadow-sm hover:shadow-md';
            @endphp

            <button type="button"
                    @if(!$notApproved)
                        onclick="openModal({{ $position->id }}, {{ $cand->id }}, @js($name))"
                    @endif
                    @if($notApproved) disabled aria-disabled="true" @endif
                    class="{{ $btnClass }}">

                <div class="flex items-start gap-4">
                    {{-- Candidate Photo / Avatar --}}
                    <div class="shrink-0">
                        @if($img)
                            <img src="{{ $img }}" alt="candidate photo"
                                 class="w-16 h-16 rounded-2xl object-cover border border-gray-200 bg-slate-100">
                        @else
                            <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-extrabold text-lg">
                                {{ $ini }}
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-extrabold text-gray-900 truncate">
                                    {{ $name ?: 'Unknown Student' }}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    Reg: <span class="font-semibold text-gray-700">{{ $reg }}</span>
                                </div>
                            </div>

                            {{-- Vote pill OR Pending pill --}}
                            @if($notApproved)
                                <span class="inline-flex items-center px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-xs font-extrabold shrink-0">
                                    Pending
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold shrink-0">
                                    Vote
                                </span>
                            @endif
                        </div>

                        <div class="mt-2 text-xs text-gray-600">
                            <div><span class="font-semibold">Faculty:</span> {{ $faculty }}</div>
                            <div class="mt-0.5"><span class="font-semibold">Program:</span> {{ $program }}</div>
                        </div>

                        {{-- Candidate manifesto / description --}}
                        @if($bio !== '')
                            <div class="mt-3 text-sm text-gray-700 leading-snug">
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">About</div>
                                <p class="mt-1 line-clamp-3">{{ $bio }}</p>
                            </div>
                        @endif

                        {{-- Vice block (ONLY if vice exists) --}}
                        @if($vice && $vs)
                            <div class="mt-4 border-t border-gray-200 pt-3">
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Vice Candidate</div>

                                <div class="mt-2 flex items-start gap-3">
                                    <div class="shrink-0">
                                        @if($vImg)
                                            <img src="{{ $vImg }}" alt="vice photo"
                                                 class="w-12 h-12 rounded-xl object-cover border border-gray-200 bg-slate-100">
                                        @else
                                            <div class="w-12 h-12 rounded-xl bg-sky-600 text-white flex items-center justify-center font-extrabold">
                                                {{ $vIni }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-gray-900 truncate">
                                            {{ $vName ?: 'Unknown Vice' }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            Reg: <span class="font-semibold text-gray-700">{{ $vReg }}</span>
                                        </div>

                                        @if($vBio !== '')
                                            <div class="mt-2 text-sm text-gray-700 leading-snug">
                                                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">About Vice</div>
                                                <p class="mt-1 line-clamp-2">{{ $vBio }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Pending approval note --}}
                        @if($notApproved)
                            <div class="mt-3 inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700">
                                Awaiting approval (you can’t vote yet)
                            </div>
                        @endif
                    </div>
                </div>
            </button>

        @empty
            <div class="col-span-2 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-xl">
                No candidates were assigned for this position.
            </div>
        @endforelse
    </div>
</div>

{{-- Tailwind line clamp (if not enabled via plugin) --}}
<style>
    .line-clamp-3{
        display:-webkit-box;
        -webkit-line-clamp:3;
        -webkit-box-orient:vertical;
        overflow:hidden;
    }
    .line-clamp-2{
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        overflow:hidden;
    }
</style>
