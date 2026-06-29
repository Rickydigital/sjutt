@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header">
        <strong class="card-title">System Settings</strong>
        <small class="text-muted d-block">Manage global system-wide settings</small>
    </div>

    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <!-- Login/Logout Setting Card -->
                <div class="card border mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-2">Login/Logout Control</h5>
                                <p class="card-text text-muted mb-3">
                                    When <strong>disabled</strong>, students cannot login or logout. This prevents credential sharing during elections.
                                </p>
                                <div class="mb-3">
                                    <strong class="d-block mb-2">Current Status:</strong>
                                    <span class="badge {{ $settings['allow_login_logout'] ? 'bg-success' : 'bg-danger' }} p-2">
                                        <i class="bi {{ $settings['allow_login_logout'] ? 'bi-unlock-fill' : 'bi-lock-fill' }} me-1"></i>
                                        {{ $settings['allow_login_logout'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>

                            <form action="{{ route('system-settings.toggle-login-logout') }}" method="POST" class="ms-3">
                                @csrf
                                <button type="submit" class="btn {{ $settings['allow_login_logout'] ? 'btn-warning' : 'btn-success' }}">
                                    <i class="bi {{ $settings['allow_login_logout'] ? 'bi-lock-fill' : 'bi-unlock-fill' }} me-2"></i>
                                    {{ $settings['allow_login_logout'] ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> The 12-hour voting lockout period will still apply after students vote, in addition to this global setting.
                </div>
            </div>

            <div class="col-md-4">
                <!-- Info Panel -->
                <div class="card bg-light border">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="bi bi-question-circle me-2"></i> How to Use
                        </h6>
                        <ul class="small mb-0">
                            <li class="mb-2"><strong>Enable:</strong> Students can login/logout normally</li>
                            <li class="mb-2"><strong>Disable:</strong> Login & logout will be blocked</li>
                            <li class="mb-2">Changes apply immediately</li>
                            <li>Useful during election periods</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
@endsection
