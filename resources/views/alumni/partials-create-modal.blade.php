<div class="modal fade" id="addAlumniModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form method="POST" action="{{ route('alumni.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add Alumni</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('alumni.partials-form', ['alumnus' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success">Create Alumni</button>
                </div>
            </div>
        </form>
    </div>
</div>
