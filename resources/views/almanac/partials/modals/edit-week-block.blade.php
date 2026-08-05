@if($setup)
<div class="modal fade" id="editWeekBlockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="editWeekBlockForm" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Edit Week Block</h5>
                        <small class="text-muted">Generated blocks can be adjusted individually here.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    @include('almanac.partials.week-block-fields')
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-danger" id="deleteWeekBlockBtn">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Week Block</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
