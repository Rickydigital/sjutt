<div class="modal fade" id="importAlumniModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('alumni.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Import Alumni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Excel / CSV File</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <div class="alert alert-info mt-3 small">
                        Import accepts headings like: email, first_name, middle_name, last_name, phone, gender, graduation_year, faculty, program, employment_state, employment_sector, organization, country, region, city, nida_number.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-warning">Import</button>
                </div>
            </div>
        </form>
    </div>
</div>
