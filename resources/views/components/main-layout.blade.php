<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UniMap</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/logos/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('assets/bootstrap-5.0.2/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/styles.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Tailwind CSS CDN for SweetAlert styling -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body>
    <!-- Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        @include('components.side-bar')
        <!-- Main wrapper -->
        <div class="body-wrapper">
            @include('components.top-bar')
            <div class="container-fluid">
                @session('error')
                    <div class="d-flex flex-column justify-content-center align-items-center my-3">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <span><strong>Error: </strong>{{ session('error') }}</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-hidden="true"
                                aria-label="Close"></button>
                        </div>
                    </div>
                @endsession

                @session('success')
                    <div class="d-flex flex-column justify-content-center align-items-center my-3">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <span><strong>Success: </strong>{{ session('success') }}</span>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Debug: Log errors to console
            var errors = @json($errors->all());
            console.log('Errors in $errors collection:', errors);

            if (errors.length > 0) {
                var errorList = errors.map(function (error) {
                    return `<li class="text-red-600">${error}</li>`;
                }).join('');

                Swal.fire({
                    title: 'Error',
                    html: `
                        <div class="p-4 bg-white rounded-lg shadow-md">
                            <ul class="list-disc list-inside text-left">${errorList}</ul>
                            <div class="mt-4 flex justify-end">
                                <button class="swal2-confirm bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" onclick="Swal.close()">Close</button>
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    width: '32rem',
                    background: 'transparent',
                    backdrop: 'rgba(0, 0, 0, 0.5)',
                });
            }
        });
    </script>

    <script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/sidebarmenu.js') }}"></script>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/dist/simplebar.js') }}"></script>
    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
</body>

</html>