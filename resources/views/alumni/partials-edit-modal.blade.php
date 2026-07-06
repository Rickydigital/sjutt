<div class="modal fade" id="editAlumniModal{{ $alumnus->id }}" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form method="POST" action="{{ route('alumni.update', $alumnus) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Alumni</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('alumni.partials-form', ['alumnus' => $alumnus])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Update Alumni</button>
                </div>
            </div>
        </form>
    </div>
</div>
