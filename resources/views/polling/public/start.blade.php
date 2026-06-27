<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Polling Centre Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card shadow border-0 w-100" style="max-width: 520px;">
        <div class="card-body p-4">

            <div class="text-center mb-4">
                <h4 class="fw-bold mb-1">Polling Centre</h4>
                <p class="text-muted mb-0">{{ $centre->name }}</p>
                <small class="text-muted">{{ $centre->election->title }}</small>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="alert alert-info small">
                Step 1 of 2: Enter your Registration Number to begin verification.
            </div>

            <form method="POST" action="{{ route('polling.public.verify-regno', $token) }}">
                
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Registration Number</label>
                    <input type="text"
                           name="reg_no"
                           class="form-control form-control-lg"
                           placeholder="Example: BAED/001/2025"
                           value="{{ old('reg_no') }}"
                           autofocus
                           required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">
                    Verify Registration Number
                </button>
            </form>

        </div>
    </div>
</div>
</body>
</html>