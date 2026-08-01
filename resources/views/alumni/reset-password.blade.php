<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — SJUT Alumni</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f6f9; }
        .reset-card { max-width: 440px; margin: 80px auto; }
    </style>
</head>
<body>
<div class="container reset-card">
    <div class="text-center mb-4">
        <img src="{{ asset('assets/img/sjutlogo.png') }}" alt="SJUT" height="60" onerror="this.style.display='none'">
        <h4 class="mt-3 fw-bold">SJUT Alumni</h4>
        <p class="text-muted small">Reset your account password</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="mb-1">Hello, {{ $alumnus->f_name }} {{ $alumnus->l_name }}</h5>
            <p class="text-muted small mb-4">{{ $alumnus->email }}</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('alumni.reset.submit', ['alumnus' => $alumnus->id, 'signature' => request('signature'), 'expires' => request('expires')]) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           placeholder="Minimum 6 characters" required minlength="6">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Set New Password</button>
            </form>
        </div>
    </div>

    <p class="text-center text-muted small mt-3">
        After resetting, open the SJUT App and log in with your new password.
    </p>
</div>
</body>
</html>
