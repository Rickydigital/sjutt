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
    $totalToVote = $elections->sum(fn ($e) => $e->positions->count());
    $votesCast   = (int) ($session->votes_cast ?? 0);
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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root{
            --ink:#0d0d0c;--ink2:#4a4946;--ink3:#9a9894;
            --rule:#e2e0da;--bg:#f7f6f2;--brand:#6c5ce7;--brand-light:#ede9fd;
        }
        body{background:var(--bg);font-family:'DM Sans',sans-serif;color:var(--ink);min-height:100vh}

        /* HEADER */
        .site-header{background:#fff;border-bottom:1.5px solid var(--ink);}
        .header-inner{max-width:900px;margin:0 auto;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;}
        .header-brand{display:flex;align-items:center;gap:14px;}
        .logo-mark{width:38px;height:38px;background:var(--ink);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .logo-mark img{width:24px;height:24px;object-fit:contain;filter:brightness(0) invert(1);}
        .brand-text{font-size:17px;font-weight:700;letter-spacing:-0.3px;color:var(--ink);}
        .brand-text small{display:block;font-size:11px;font-weight:400;color:var(--ink3);letter-spacing:0.02em;margin-top:1px;}
        .progress-strip{display:flex;align-items:center;gap:12px;}
        .prog-label{font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--ink3);}
        .prog-track{width:120px;height:3px;background:var(--rule);}
        .prog-fill{height:100%;background:var(--brand);}
        .prog-count{font-size:12px;font-weight:700;color:var(--ink2);}

        /* VOTER STRIP */
        .voter-strip{background:var(--ink);color:#fff;border-bottom:1.5px solid var(--ink);position:relative;overflow:hidden;}
        .voter-wm{position:absolute;right:-20px;top:50%;transform:translateY(-50%);opacity:.04;pointer-events:none;}
        .voter-wm img{width:160px;height:160px;object-fit:contain;filter:brightness(0) invert(1);}
        .voter-inner{max-width:900px;margin:0 auto;padding:22px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;}
        .voter-eyebrow{font-size:10px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:6px;}
        .voter-name{font-family:'DM Serif Display',serif;font-size:26px;font-weight:400;color:#fff;line-height:1.1;}
        .voter-reg{font-size:13px;color:rgba(255,255,255,.5);margin-top:4px;}
        .voter-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;}
        .voter-tag{font-size:11px;font-weight:500;padding:3px 10px;border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.55);}
        .voter-avatar{flex-shrink:0;width:52px;height:52px;border:1.5px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;font-size:20px;color:#fff;}

        /* MAIN */
        .main{max-width:900px;margin:0 auto;padding:0 24px 80px;}

        /* ELECTION */
        .election-block{border-top:1.5px solid var(--ink);padding-top:32px;margin-top:32px;}
        .election-block:first-child{border-top:none;}
        .election-eyebrow{font-size:10px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink3);margin-bottom:6px;}
        .election-title{font-family:'DM Serif Display',serif;font-size:22px;color:var(--ink);margin-bottom:4px;}
        .election-sub{font-size:12px;color:var(--ink3);}

        /* POSITION */
        .position-block{margin-top:28px;}
        .position-header{display:flex;align-items:baseline;justify-content:space-between;border-bottom:1px solid var(--ink);padding-bottom:8px;}
        .position-name{font-size:13px;font-weight:700;letter-spacing:0.02em;text-transform:uppercase;color:var(--ink);}
        .position-scope{font-size:11px;color:var(--ink3);}
        .position-count{font-size:11px;font-weight:600;color:var(--ink3);}

        /* CANDIDATES */
        .c-row{display:block;position:relative;border-bottom:1px solid var(--rule);padding:16px 0;cursor:pointer;}
        .c-row:last-of-type{border-bottom:none;}
        .c-radio{position:absolute;opacity:0;pointer-events:none;}
        .c-radio:checked ~ .c-inner{background:var(--brand-light);}
        .c-radio:checked ~ .c-sel-bar{opacity:1;}
        .c-sel-bar{position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--brand);opacity:0;transition:opacity .12s;}
        .c-inner{display:flex;align-items:flex-start;gap:14px;padding:0 12px 0 14px;transition:background .12s;}
        .c-photo,.c-initials{width:56px;height:56px;flex-shrink:0;border:1px solid var(--rule);}
        .c-photo{object-fit:cover;}
        .c-initials{background:#e8e6de;display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;font-size:18px;color:var(--ink2);}
        .c-info{flex:1;}
        .c-name{font-size:15px;font-weight:700;color:var(--ink);line-height:1.2;}
        .c-reg{font-size:11px;color:var(--ink3);margin-top:2px;}
        .c-tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px;}
        .c-tag{font-size:10px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;padding:2px 7px;border:1px solid var(--rule);color:var(--ink2);background:#fff;}
        .c-desc{font-size:12px;color:var(--ink3);margin-top:6px;line-height:1.5;}
        .c-check{flex-shrink:0;width:22px;height:22px;border:1.5px solid var(--rule);display:flex;align-items:center;justify-content:center;margin-top:2px;transition:border-color .13s,background .13s;}
        .c-radio:checked ~ .c-inner .c-check{border-color:var(--brand);background:var(--brand);}
        .c-check-icon{width:11px;height:11px;opacity:0;transition:opacity .13s;}
        .c-radio:checked ~ .c-inner .c-check-icon{opacity:1;}

        /* VICE */
        .vice-block{margin:10px 14px 0;padding:10px 12px;border-left:2px solid var(--rule);display:flex;align-items:center;gap:10px;}
        .vice-photo,.vice-initials{width:36px;height:36px;flex-shrink:0;border:1px solid var(--rule);}
        .vice-photo{object-fit:cover;}
        .vice-initials{background:#e8e6de;display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;font-size:12px;color:var(--ink2);}
        .vice-label{font-size:9px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--ink3);margin-bottom:2px;}
        .vice-name{font-size:13px;font-weight:600;color:var(--ink2);}
        .vice-reg{font-size:11px;color:var(--ink3);}

        /* VOTE BTN */
        .vote-btn{display:block;width:100%;margin-top:16px;padding:15px;background:var(--ink);color:#fff;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:background .13s;text-align:center;}
        .vote-btn:hover{background:#2d2b28;}

        /* ALERTS */
        .alert{padding:12px 16px;margin:16px 0;font-size:13px;border-left:3px solid;}
        .alert-ok{border-color:#22c55e;background:#f0fdf4;color:#166534;}
        .alert-err{border-color:#ef4444;background:#fef2f2;color:#991b1b;}

        /* COMPLETE */
        .complete-block{padding:60px 0 40px;text-align:center;}
        .complete-title{font-family:'DM Serif Display',serif;font-size:32px;color:var(--ink);margin-bottom:8px;}
        .complete-sub{font-size:14px;color:var(--ink3);max-width:380px;margin:0 auto 28px;}
        .complete-btn{display:inline-block;padding:14px 36px;background:var(--ink);color:#fff;font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;}
        .complete-btn:hover{background:#2d2b28;}

        .no-candidates{padding:14px 0;font-size:12px;color:var(--ink3);border-bottom:1px solid var(--rule);}
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <div class="header-brand">
            <div class="logo-mark">
                <img src="{{ asset('images/logo.png') }}" alt="Super">
            </div>
            <div class="brand-text">
                Super
                <small>{{ $centre->name }} · {{ $centre->election->title }}</small>
            </div>
        </div>
        <div class="progress-strip">
            <span class="prog-label">Progress</span>
            <div class="prog-track"><div class="prog-fill" style="width:{{ $progressPercent }}%"></div></div>
            <span class="prog-count">{{ $votesCast }} of {{ $totalInSession }}</span>
        </div>
    </div>
</header>

<div class="voter-strip">
    <div class="voter-wm">
        <img src="{{ asset('images/logo.png') }}" alt="">
    </div>
    <div class="voter-inner">
        <div>
            <div class="voter-eyebrow">Verified Voter</div>
            <div class="voter-name">{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</div>
            <div class="voter-reg">{{ $student->reg_no }}</div>
            <div class="voter-tags">
                <span class="voter-tag">{{ $student->faculty->name ?? '—' }}</span>
                <span class="voter-tag">{{ $student->program->short_name ?? $student->program->name ?? '—' }}</span>
            </div>
        </div>
        <div class="voter-avatar">{{ pollingInitials($student->first_name, $student->last_name) }}</div>
    </div>
</div>

<div class="main">

    @if(session('success'))
        <div class="alert alert-ok">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-err">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if($elections->isEmpty() || $totalToVote === 0)
        <div class="complete-block">
            <div style="font-size:40px;margin-bottom:20px;">✓</div>
            <div class="complete-title">Voting complete</div>
            <div class="complete-sub">All positions voted. The session can now be closed for the next student.</div>
            <form method="POST" action="{{ route('polling.public.finish', $token) }}">
                @csrf
                <button class="complete-btn" type="submit">Finish &amp; next student →</button>
            </form>
        </div>
    @else
        @foreach($elections as $election)
            <div class="election-block">
                <div class="election-eyebrow">Election</div>
                <div class="election-title">{{ $election->title }}</div>
                <div class="election-sub">Choose one candidate per position. Your vote is final once submitted.</div>

                @foreach($election->positions as $position)
                    <div class="position-block">
                        <div class="position-header">
                            <div>
                                <div class="position-name">{{ $position->definition->name ?? 'Election Position' }}</div>
                                <div class="position-scope">{{ pollingScopeLabel($position->scope_type) }}</div>
                            </div>
                            <div class="position-count">{{ $position->candidates->count() }} candidate(s)</div>
                        </div>

                        @if($position->candidates->isEmpty())
                            <div class="no-candidates">No approved candidates for this position.</div>
                        @else
                            <form method="POST" action="{{ route('polling.public.vote.store', $token) }}" class="vote-form">
                                @csrf
                                <input type="hidden" name="election_position_id" value="{{ $position->id }}">

                                <div class="candidates-list">
                                    @foreach($position->candidates as $candidate)
                                        @php
                                            $cs = $candidate->student;
                                            $photoUrl = $candidate->photo_url;
                                            $cName = trim(($cs->first_name ?? '') . ' ' . ($cs->last_name ?? ''));
                                        @endphp
                                        <label class="c-row">
                                            <input type="radio" name="candidate_id" value="{{ $candidate->id }}" required class="c-radio">
                                            <div class="c-sel-bar"></div>
                                            <div class="c-inner">
                                                @if($photoUrl)
                                                    <img src="{{ $photoUrl }}" class="c-photo" alt="">
                                                @else
                                                    <div class="c-initials">{{ pollingInitials($cs->first_name ?? '', $cs->last_name ?? '') }}</div>
                                                @endif
                                                <div class="c-info">
                                                    <div class="c-name">{{ $cName ?: 'Candidate' }}</div>
                                                    <div class="c-reg">{{ $cs->reg_no ?? '—' }}</div>
                                                    <div class="c-tags">
                                                        @if($cs->faculty)<span class="c-tag">{{ $cs->faculty->name }}</span>@endif
                                                        @if($cs->program)<span class="c-tag">{{ $cs->program->short_name ?? $cs->program->name }}</span>@endif
                                                    </div>
                                                    @if($candidate->description)
                                                        <div class="c-desc">{{ $candidate->description }}</div>
                                                    @endif
                                                </div>
                                                <div class="c-check">
                                                    <svg class="c-check-icon" viewBox="0 0 12 12" fill="none">
                                                        <polyline points="2,6 5,9 10,3" stroke="white" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            @if($candidate->vice)
                                                @php $vice = $candidate->vice; $vs = $vice->student; @endphp
                                                <div class="vice-block">
                                                    @if($vice->photo_url)
                                                        <img src="{{ $vice->photo_url }}" class="vice-photo" alt="">
                                                    @else
                                                        <div class="vice-initials">{{ pollingInitials($vs->first_name ?? '', $vs->last_name ?? '') }}</div>
                                                    @endif
                                                    <div>
                                                        <div class="vice-label">Running Mate</div>
                                                        <div class="vice-name">{{ $vs->first_name ?? '' }} {{ $vs->last_name ?? '' }}</div>
                                                        <div class="vice-reg">{{ $vs->reg_no ?? '—' }}</div>
                                                    </div>
                                                </div>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>

                                <button class="vote-btn" type="submit">Cast vote for this position →</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
</div>

<script>
document.querySelectorAll('.vote-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const sel = form.querySelector('input[name="candidate_id"]:checked');
        if (!sel) { e.preventDefault(); alert('Select a candidate first.'); return; }
        const name = sel.closest('label').querySelector('.c-name')?.innerText || 'this candidate';
        if (!confirm('Confirm vote for ' + name + '?')) e.preventDefault();
    });
});
</script>
</body>
</html>