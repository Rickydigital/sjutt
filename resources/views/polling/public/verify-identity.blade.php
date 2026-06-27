<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Identity</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card shadow border-0 w-100" style="max-width: 560px;">
        <div class="card-body p-4">

            <div class="text-center mb-4">
                <h4 class="fw-bold mb-1">Identity Verification</h4>
                <p class="text-muted mb-0">{{ $centre->name }}</p>
                <small class="text-muted">{{ $centre->election->title }}</small>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="alert alert-success">
                Registration number found.
            </div>

            <div class="border rounded p-3 mb-3 bg-white">
                <div class="small text-muted">Student</div>
                <div class="fw-bold">{{ $masked_name }}</div>

                <div class="row mt-2">
                    <div class="col-6">
                        <small class="text-muted d-block">Program</small>
                        <span>{{ $student->program->short_name ?? $student->program->name ?? '—' }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Class</small>
                        <span>{{ $student->faculty->name ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="alert alert-info small">
                Step 2 of 2: Enter your Form Four Index Number and Last Name to continue.
            </div>

            <form method="POST" action="{{ route('polling.public.verify-identity', $token) }}">
                @csrf

                <input type="hidden" name="session_token" value="{{ $session_token }}">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Form Four Index Number</label>
                    <input type="text"
                           name="form4_index"
                           class="form-control form-control-lg"
                           placeholder="Example: S1234/0056/2021"
                           required
                           autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Last Name</label>
                    <input type="text"
                           name="last_name"
                           class="form-control form-control-lg"
                           placeholder="Enter your last name"
                           required>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100">
                    Proceed to Vote
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('polling.public.show', $token) }}" class="text-muted">
                    Start again
                </a>
            </div>

        </div>
    </div>
</div>
</body>
</html>