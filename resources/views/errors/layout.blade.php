<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Error</title>

    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <style>
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            background: linear-gradient(to bottom, #f7fafc, #e2e8f0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .robot {
            width: 120px;
            height: 120px;
            background: url('https://img.icons8.com/?size=100&id=13014&format=png&color=000000') no-repeat center;
            background-size: contain;
            animation: bounce 1s infinite alternate;
            margin: 0 auto;
        }
        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-15px); }
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #e3342f;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        .error-message {
            font-size: 1.5rem;
            color: #4a5568;
            margin-top: 1rem;
        }
        .dashboard-btn {
            background-color: #4299e1;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-top: 1.5rem;
            transition: background-color 0.3s;
        }
        .dashboard-btn:hover {
            background-color: #2b6cb0;
        }
    </style>
</head>
<body class="antialiased">
    <div class="text-center">
        <div class="robot"></div>
        <div class="error-code">@yield('code')</div>
        <div class="error-message">@yield('message')</div>
        <a href="{{ route('student.vote.index') }}" class="dashboard-btn pulse">Back</a>
    </div>
</body>
</html>