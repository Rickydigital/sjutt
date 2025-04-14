<!DOCTYPE html>
<html>
<head>
    <title>Suggestion #{{ $suggestion->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-container {
            max-width: 600px;
            margin: auto;
            border: 1px solid #ddd;
            padding: 10px;
            height: 400px;
            overflow-y: scroll;
        }
        .message {
            margin-bottom: 10px;
        }
        .message.known {
            text-align: right;
        }
        .message.anonymous {
            text-align: left;
        }
        .message-content {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 10px;
            background: #e0e0e0;
        }
        .known .message-content {
            background: #d1e7dd;
        }
        .status-time {
            font-size: 0.8em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Suggestion #{{ $suggestion->id }}</h1>
        <div class="chat-container">
            <div class="message {{ $suggestion->is_anonymous ? 'anonymous' : 'known' }}">
                @if (!$suggestion->is_anonymous)
                    <div>
                        <img src="{{ asset('images/user-icon.png') }}" alt="User" width="30">
                        <strong>{{ $suggestion->student->name }}</strong> ({{ $suggestion->student->reg_no }})
                    </div>
                @else
                    <div><strong>Anonymous</strong></div>
                @endif
                <div class="message-content">{{ $suggestion->message }}</div>
                <div class="status-time">
                    <span>{{ $suggestion->status }}</span> | <span>{{ $suggestion->created_at->format('H:i d/m/Y') }}</span>
                </div>
            </div>
        </div>
        <a href="{{ route('admin.suggestions.index') }}" class="btn btn-secondary mt-3">Back</a>
    </div>
</body>
</html>