{{-- resources/views/admin/suggestions/index.blade.php --}}
@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header" style="background: linear-gradient(135deg, #8b5cf6, #5b21b6); color: white;">
        <h4 class="mb-0">Anonymous Student Suggestions</h4>
    </div>

    <!-- Stats -->
    <div class="card-body border-bottom">
        <div class="row text-center">
            <div class="col">
                <h5 class="text-primary">{{ $total }}</h5>
                <small class="text-muted">Total</small>
            </div>
            <div class="col">
                <h5 class="text-danger">{{ $received }}</h5>
                <small class="text-muted">New (Received)</small>
            </div>
            <div class="col">
                <h5 class="text-warning">{{ $viewed }}</h5>
                <small class="text-muted">Viewed</small>
            </div>
            <div class="col">
                <h5 class="text-success">{{ $processed }}</h5>
                <small class="text-muted">Processed</small>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        @if($suggestions->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-chat-square-text display-1"></i>
                <p class="mt-3">No suggestions yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Message Preview</th>
                            <th>Sent</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suggestions as $suggestion)
                            <tr>
                                <td>{{ $loop->iteration + ($suggestions->currentPage() - 1) * $suggestions->perPage() }}</td>
                                <td>
                                    <strong>{{ Str::limit($suggestion->message, 60) }}</strong>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $suggestion->created_at->diffForHumans() }}
                                        <br>
                                        {{ $suggestion->created_at->format('d M h:i A') }}
                                    </small>
                                </td>
                                <td>
                                    @if($suggestion->status === 'Received')
                                        <span class="badge bg-danger">New</span>
                                    @elseif($suggestion->status === 'Viewed')
                                        <span class="badge bg-warning text-dark">Viewed</span>
                                    @elseif($suggestion->status === 'Processed')
                                        <span class="badge bg-success">Processed</span>
                                    @endif
                                </td>
                                <td>
                                    <!-- View Button -->
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                            data-bs-toggle="modal" data-bs-target="#viewModal"
                                            onclick="loadMessage({{ $suggestion->id }})">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Process Button (only if not Processed) -->
                                    @if($suggestion->status !== 'Processed')
                                        <button type="button" class="btn btn-sm btn-outline-success"
                                                onclick="markProcessed({{ $suggestion->id }})">
                                            <i class="bi bi-check2-all"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-light">
                {{ $suggestions->links() }}
            </div>
        @endif
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Anonymous Message</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="messageBody">
                <p class="text-center"><i class="bi bi-hourglass-split"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <small class="text-muted" id="messageTime"></small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function loadMessage(id) {
    fetch(`/admin-suggestions/${id}/message`)
        .then(response => {
            if (!response.ok) throw new Error('Not found');
            return response.json();
        })
        .then(data => {
            document.getElementById('messageBody').innerHTML = `
                <div class="p-4 bg-light rounded shadow-sm">
                    <p class="mb-0 whitespace-pre-wrap">${data.message.replace(/\n/g, '<br>')}</p>
                </div>
                <div class="mt-3 text-end">
                    <em class="text-muted">— Anonymous Student</em>
                </div>
            `;
            document.getElementById('messageTime').textContent = data.created_at;

            // Auto mark as Viewed
            if (data.status === 'Received') {
                fetch(`/admin-suggestions/${id}/viewed`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                // Update badge instantly
                const btn = document.querySelector(`button[onclick="loadMessage(${id})"]`);
                const row = btn.closest('tr');
                const badge = row.querySelector('.badge');
                badge.className = 'badge bg-warning text-dark';
                badge.textContent = 'Viewed';
            }
        })
        .catch(err => {
            document.getElementById('messageBody').innerHTML = `
                <div class="text-danger text-center">
                    <i class="bi bi-exclamation-triangle"></i> Failed to load message
                </div>`;
            console.error(err);
        });
}

function markProcessed(id) {
    if (!confirm('Mark as Processed?')) return;

    fetch(`/admin-suggestions/${id}/processed`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(() => location.reload())
    .catch(() => alert('Failed to update'));
}
</script>
@endsection