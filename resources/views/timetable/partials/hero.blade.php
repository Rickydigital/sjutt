<style>
    :root {
        --tt-primary: #4b2e83;
        --tt-primary-dark: #2f1c57;
        --tt-secondary: #6f42c1;
        --tt-accent: #ede7f6;
        --tt-bg: #f8f7fc;
        --tt-surface: #ffffff;
        --tt-border: #e3def0;
        --tt-success: #198754;
        --tt-warning: #ffc107;
        --tt-danger: #dc3545;
        --tt-text: #2d2a32;
        --tt-muted: #6c757d;
        --tt-shadow: 0 10px 30px rgba(75, 46, 131, 0.10);
        --tt-radius: 18px;
    }

    .tt-page {
        color: var(--tt-text);
    }

    .tt-hero {
        background: linear-gradient(135deg, var(--tt-primary), var(--tt-secondary));
        color: #fff;
        border-radius: 24px;
        padding: 1.5rem;
        box-shadow: var(--tt-shadow);
        margin-bottom: 1.5rem;
    }

    .tt-hero-title {
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: .35rem;
    }

    .tt-hero-subtitle {
        opacity: .9;
        margin-bottom: 0;
    }

    .tt-chip {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem .9rem;
        border-radius: 999px;
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.22);
        font-size: .9rem;
        font-weight: 600;
    }

    .tt-card {
        background: var(--tt-surface);
        border: 1px solid var(--tt-border);
        border-radius: var(--tt-radius);
        box-shadow: var(--tt-shadow);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .tt-card-header {
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, var(--tt-primary), var(--tt-secondary));
        color: #fff;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .tt-card-body {
        padding: 1.25rem;
    }

    .tt-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1rem;
    }

    .tt-stat {
        border: 1px solid var(--tt-border);
        border-radius: 16px;
        padding: 1rem;
        background: linear-gradient(180deg, #ffffff, #faf8ff);
    }

    .tt-stat-label {
        font-size: .84rem;
        color: var(--tt-muted);
        margin-bottom: .4rem;
    }

    .tt-stat-value {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--tt-primary);
    }

    .tt-toolbar {
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
    }

    .tt-btn {
        border-radius: 12px !important;
        font-weight: 600;
        padding: .65rem 1rem;
    }

    .tt-btn-primary {
        background: linear-gradient(135deg, var(--tt-primary), var(--tt-secondary));
        border: none;
        color: #fff;
    }

    .tt-btn-primary:hover {
        color: #fff;
        opacity: .95;
    }

    .tt-btn-soft {
        background: #f4efff;
        color: var(--tt-primary);
        border: 1px solid #d7c8fb;
    }

    .tt-btn-soft:hover {
        background: #ece3ff;
        color: var(--tt-primary);
    }

    .tt-warning-box {
        border: 1px solid #ffe4a3;
        background: #fff9e8;
        color: #8a6300;
        border-radius: 14px;
        padding: .9rem 1rem;
    }

    .tt-form-note {
        font-size: .82rem;
        color: var(--tt-muted);
    }

    .tt-inline-note {
        font-size: .78rem;
        color: var(--tt-muted);
    }

    .modal-content {
        border-radius: 20px;
        border: 1px solid var(--tt-border);
        box-shadow: var(--tt-shadow);
        overflow: hidden;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--tt-primary), var(--tt-secondary));
        color: #fff;
        border-bottom: none;
    }

    .modal-body {
        padding: 1.25rem;
        max-height: 75vh;
        overflow-y: auto;
    }

    .modal-footer {
        border-top: 1px solid var(--tt-border);
    }

    .form-label {
        font-weight: 700;
        color: var(--tt-primary-dark);
    }

    .form-control,
    .form-select,
    .select2-container--classic .select2-selection--single,
    .select2-container--classic .select2-selection--multiple {
        border-radius: 12px !important;
        border: 1px solid #d8cfee !important;
        min-height: 44px;
    }

    .select2-container--classic .select2-selection--multiple {
        padding: .2rem .35rem;
    }

    .select2-container--open {
        z-index: 999999 !important;
    }

    .select2-dropdown {
        z-index: 999999 !important;
    }

    .tt-cross-note {
        display: none;
        margin-top: .35rem;
        color: var(--tt-secondary);
        font-size: .78rem;
        font-weight: 600;
    }

    .tt-course-selection {
        border: 1px solid var(--tt-border);
        border-radius: 16px;
        padding: 1rem;
        background: #fbf9ff;
        margin-bottom: 1rem;
    }
</style>

<div class="tt-page">
    <div class="tt-hero">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="tt-hero-title">
                    <i class="fas fa-calendar-alt me-2"></i> Timetable Management
                </div>
                <p class="tt-hero-subtitle">
                    Manage manual entries, generate sessions, switch timetable setups, activate the correct setup, and handle cross-catering consistently.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <span class="tt-chip">
                    <i class="fas fa-layer-group"></i>
                    {{ $currentSemesterLabel }}
                </span>

                @if($facultyId)
                    <span class="tt-chip">
                        <i class="fas fa-building"></i>
                        {{ $faculties[$facultyId] ?? 'Selected Faculty' }}
                    </span>
                @endif

                @if($timetableSemester)
                    <span class="tt-chip">
                        <i class="fas fa-toggle-on"></i>
                        {{ ucfirst($timetableSemester->status ?? 'draft') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if(!empty($error))
        <div class="tt-warning-box mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ $error }}
        </div>
    @endif