@extends('layouts.admin')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Conversations</h3>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse($conversations as $conversation)
                        @php
                            $hasNonAnonymous = \App\Models\Suggestion::where('student_id', $conversation->student_id)
                                ->where('sender_type', 'student')
                                ->where('is_anonymous', false)
                                ->exists();
                            $title = $hasNonAnonymous && $conversation->student ? $conversation->student->name : 'Anonymous';
                        @endphp
                        <li class="list-group-item">
                            <a href="{{ route('admin.suggestions.conversation', $conversation->student_id) }}" class="d-flex justify-content-between align-items-center">
                                <span>{{ $title }}</span>
                                <small class="text-muted">{{ $conversation->last_message_at->diffForHumans() }}</small>
                            </a>
                        </li>
                    @empty
                        <li class="list-group-item">No conversations found.</li>
                    @endforelse
                </ul>
                {{ $conversations->links() }}
            </div>
        </div>
    </div>
@endsection