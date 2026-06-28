<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Polling Centre Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c29 0%, #1a1040 40%, #0d0d2b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .watermark {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 0;
        }

        .watermark img {
            width: 420px;
            height: 420px;
            object-fit: contain;
            opacity: 0.045;
            filter: grayscale(1) brightness(10);
        }

        .card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px;
            backdrop-filter: blur(24px);
            padding: 2.5rem 2rem;
            box-shadow: 0 8px 64px rgba(0,0,0,0.5);
        }

        .logo-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 2rem;
        }

        .logo-row img {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }

        .brand-name {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .brand-name span { color: #7c6af7; }

        .divider {
            height: 1px;
            background: rgba(255,255,255,0.08);
            margin: 0 -2rem 2rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(124,106,247,0.18);
            border: 1px solid rgba(124,106,247,0.35);
            color: #a99ef8;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 100px;
            margin-bottom: 1.25rem;
        }

        .centre-name {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .election-title {
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            margin-bottom: 2rem;
        }

        .alert-success {
            background: rgba(76,175,134,0.12);
            border: 1px solid rgba(76,175,134,0.3);
            border-left: 3px solid #4caf86;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            color: rgba(255,255,255,0.8);
            margin-bottom: 1rem;
        }

        .alert-danger {
            background: rgba(220,53,69,0.1);
            border: 1px solid rgba(220,53,69,0.25);
            border-left: 3px solid #dc3545;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            color: rgba(255,255,255,0.75);
            margin-bottom: 1rem;
        }

        .alert-info {
            background: rgba(124,106,247,0.1);
            border: 1px solid rgba(124,106,247,0.2);
            border-left: 3px solid #7c6af7;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }

        .field-label {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            display: flex;
        }

        .reg-input {
            width: 100%;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 14px 14px 14px 42px;
            font-size: 15px;
            font-weight: 500;
            color: #fff;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
        }

        .reg-input::placeholder { color: rgba(255,255,255,0.22); font-weight: 400; }
        .reg-input:focus { border-color: #7c6af7; background: rgba(124,106,247,0.08); }

        .btn-verify {
            width: 100%;
            background: linear-gradient(135deg, #7c6af7 0%, #5a47d6 100%);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-verify:hover { opacity: 0.88; }

        .footer-note {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 12px;
            color: rgba(255,255,255,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .pulse-dot {
            width: 6px; height: 6px;
            background: #4caf86;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.7); }
        }
    </style>
</head>
<body>

<div class="watermark">
    <img src="{{ asset('images/logo.png') }}" alt="">
</div>

<div class="card">
    <div class="logo-row">
        <img src="{{ asset('images/logo.png') }}" alt="Super logo">
        <div class="brand-name">Super<span>.</span></div>
    </div>

    <div class="divider"></div>

    <div class="badge">🗳 Polling Centre</div>

    <div class="centre-name">{{ $centre->name }}</div>
    <div class="election-title">{{ $centre->election->title }}</div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="alert-info">
        Enter your Registration Number to begin voting. Your number can be found on your voter registration card.
    </div>

    <div class="field-label">Registration Number</div>
    <form method="POST" action="{{ route('polling.public.verify-regno', $token) }}">
        @csrf
        <div class="input-wrap">
            <span class="input-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </span>
            <input
                type="text"
                name="reg_no"
                class="reg-input"
                placeholder="e.g. NS2022...... or 2020/....."
                value="{{ old('reg_no') }}"
                autofocus
                required
            >
        </div>
        <button type="submit" class="btn-verify">Verify Registration Number</button>
    </form>

    <div class="footer-note">
        <div class="pulse-dot"></div>
        Secure voting session
    </div>
</div>

</body>
</html>