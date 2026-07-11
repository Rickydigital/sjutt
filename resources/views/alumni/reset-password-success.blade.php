<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Successful — SJUT Alumni</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f6f9; }
        .success-card { max-width: 440px; margin: 80px auto; }
    </style>
</head>
<body>
<div class="container success-card text-center">
    <img src="{{ asset('assets/img/sjutlogo.png') }}" alt="SJUT" height="60" class="mb-3" onerror="this.style.display='none'">
    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            <div class="mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor"
                     class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
            </div>
            <h4 class="fw-bold text-success">Password Reset!</h4>
            <p class="text-muted mt-2">
                Your password has been updated successfully.
                Open the <strong>SJUT App</strong> and log in with your new password.
            </p>
        </div>
    </div>
    <p class="text-muted small mt-3">You can close this page.</p>
</div>
</body>
</html>
