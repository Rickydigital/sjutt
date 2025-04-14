<!DOCTYPE html>
<html>
<head>
    <title>Suggestions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Suggestions</h1>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Message Preview</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($suggestions as $suggestion)
                    <tr>
                        <td>{{ $suggestion->id }}</td>
                        <td>{{ $suggestion->is_anonymous ? 'Anonymous' : $suggestion->student->name }}</td>
                        <td>{{ Str::limit($suggestion->message, 50) }}</td>
                        <td>{{ $suggestion->status }}</td>
                        <td>
                            <a href="{{ route('admin.suggestions.show', $suggestion) }}" class="btn btn-primary btn-sm">View</a>
                            <form action="{{ route('admin.suggestions.updateStatus', $suggestion) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('PUT')
                                <select name="status" onchange="this.form.submit()">
                                    <option value="Received" {{ $suggestion->status === 'Received' ? 'selected' : '' }}>Received</option>
                                    <option value="Viewed" {{ $suggestion->status === 'Viewed' ? 'selected' : '' }}>Viewed</option>
                                    <option value="Processed" {{ $suggestion->status === 'Processed' ? 'selected' : '' }}>Processed</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>