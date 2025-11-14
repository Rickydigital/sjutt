@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <!-- Header: Purple Gradient -->
    <div class="card-header" style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0 text-white">Student Suggestions</h5>
            <div>
                <span class="badge bg-light text-dark me-2">
                    {{ $conversations->total() }} Conversations
                </span>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white p-3 rounded shadow-sm">
            <form action="{{ route('admin.suggestions.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label text-muted small mb-1">Search Student</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Name, email, reg no..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="Received" {{ request('status') == 'Received' ? 'selected' : '' }}>New</option>
                        <option value="Viewed" {{ request('status') == 'Viewed' ? 'selected' : '' }}>Viewed</option>
                        <option value="Processed" {{ request('status') == 'Processed' ? 'selected' : '' }}>Replied</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-0 m-0 py-2 text-center">
                {{ session('success') }}
            </div>
        @endif

        @if ($conversations->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-chat-square-text display-1"></i>
                <p class="mt-3">No conversations found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Last Message</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conversations as $conv)
                            @php
                                $student = $conv->student;
                                $lastMsg = \App\Models\Suggestion::where('student_id', $student->id)
                                    ->latest()->first();
                                $isNew = $lastMsg && $lastMsg->sender_type === 'student' && $lastMsg->status === 'Received';
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration + ($conversations->currentPage() - 1) * $conversations->perPage() }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2"
                                             style="width:40px;height:40px;">
                                            <i class="bi bi-person text-muted"></i>
                                        </div>
                                        <div>
                                            <strong>
                                                @if(!$lastMsg || !$lastMsg->is_anonymous)
                                                    {{ $student->name ?? 'Anonymous' }}
                                                @else
                                                    <em class="text-muted">Anonymous</em>
                                                @endif
                                            </strong>
                                            <br>
                                            <small class="text-muted">{{ $student->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small>{{ Str::limit($lastMsg->message ?? '', 50) }}</small>
                                    <br>
                                    <small class="text-muted">
                                        {{ $conv->last_message_at->diffForHumans() }}
                                    </small>
                                </td>
                                <td>
                                    @if($isNew)
                                        <span class="badge bg-danger">New</span>
                                    @elseif($lastMsg->status === 'Viewed')
                                        <span class="badge bg-warning text-dark">Viewed</span>
                                    @elseif($lastMsg->status === 'Processed')
                                        <span class="badge bg-success">Replied</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $lastMsg->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#conversationModal{{ $student->id }}">
                                        <i class="bi bi-chat-dots"></i> Open
                                    </button>
                                </td>
                            </tr>

                            <!-- ==================== CONVERSATION MODAL ==================== -->
                            <div class="modal fade" id="conversationModal{{ $student->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title">
                                                {{ $hasNonAnonymous ?? false ? "Conversation with {$student->name}" : "Anonymous Conversation" }}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-0">
                                            <div class="chat-container" style="max-height: 500px; overflow-y: auto;">
                                                @php
                                                    $messages = \App\Models\Suggestion::where('student_id', $student->id)
                                                        ->with(['user' => fn($q) => $q->select('id', 'name')])
                                                        ->orderBy('created_at', 'asc')
                                                        ->get();
                                                @endphp

                                                @foreach($messages as $msg)
                                                    @if($msg->sender_type === 'student')
                                                        <div class="d-flex justify-content-end mb-3 px-3">
                                                            <div class="bg-primary text-white p-3 rounded-lg shadow-sm"
                                                                 style="max-width: 80%;">
                                                                <small class="d-block text-white-50 mb-1">
                                                                    {{ $msg->is_anonymous ? 'Anonymous' : $student->name }}
                                                                </small>
                                                                {!! nl2br(e($msg->message)) !!}
                                                                <small class="d-block text-white-50 text-end mt-1">
                                                                    {{ $msg->created_at->format('d M, h:i A') }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="d-flex justify-content-start mb-3 px-3">
                                                            <div class="bg-light text-dark p-3 rounded-lg shadow-sm"
                                                                 style="max-width: 80%;">
                                                                <small class="d-block text-muted mb-1">
                                                                    {{ $msg->user?->name ?? 'Admin' }}
                                                                </small>
                                                                {!! nl2br(e($msg->message)) !!}
                                                                <small class="d-block text-muted text-end mt-1">
                                                                    {{ $msg->created_at->format('d M, h:i A') }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>

                                            <!-- Reply Form -->
                                            <form action="{{ route('admin.suggestions.reply', $student->id) }}" method="POST" class="border-top p-3">
                                                @csrf
                                                <div class="input-group">
                                                    <textarea name="message" class="form-control" rows="2"
                                                              placeholder="Type your reply..." required></textarea>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-send"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-light border-top-0">
                {{ $conversations->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
    .chat-container { background-color: #f8f9fa; }
    .rounded-lg { border-radius: 1rem; }
    .table-hover tbody tr:hover { background-color: #f1f3f5; }
</style>
@endsection