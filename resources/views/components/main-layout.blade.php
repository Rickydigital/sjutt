<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UniMap</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/logos/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('assets/bootstrap-5.0.2/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/styles.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/bootstrap-icons/bootstrap-icons.min.css') }}">
</head>

<body>
    <!--  Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        @include('components.side-bar')
        <!--  Main wrapper -->
        <div class="body-wrapper">
            @include('components.top-bar')
            <div class="container-fluid">
                @session('error')
                    <div class="d-flex flex-column justify-content-center align-items-center my-3">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <span><Strong>Error: </Strong>{{ session('error') }}</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-hidden="true"
                                aria-label="Close"></button>
                        </div>
                    </div>
                @endsession

                @session('success')
                    <div class="d-flex flex-column justify-content-center align-items-center my-3">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <span><Strong>Success: </Strong>{{ session('success') }}</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-hidden="true"
                                aria-label="Close"></button>
                        </div>
                    </div>
                @endsession
                @yield('content')
            </div>
        </div>
    </div>

    <!-- SweetAlert for Errors -->
    @if ($errors->any())
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var errors = @json($errors->all());
                if (errors.length > 0) {
                    var errorList = errors.map(function (error) {
                        return '<li>' + error + '</li>';
                    }).join('');
                    Swal.fire({
                        icon: 'error',
                        title: 'Whoops! Something went wrong.',
                        html: '<ul>' + errorList + '</ul>',
                    });
                }
            });
        </script>
    @endif
    <script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/sidebarmenu.js') }}"></script>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/dist/simplebar.js') }}"></script>
    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
</body>

</html>
