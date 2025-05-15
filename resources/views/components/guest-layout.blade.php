<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modernize Free</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/logos/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/styles.min.css') }}" />
</head>

<body>
    <!--  Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        <div
            class="position-relative overflow-hidden radial-gradient min-vh-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center justify-content-center w-100">
                <div class="row justify-content-center w-100">
                    <div class="col-md-8 col-lg-6 col-xxl-4">
                        <!-- start: page -->
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

                        <div class="card mb-0">
                            <div class="card-body">
                                <span class="text-nowrap logo-img text-center d-block py-3 w-100">
                                    <img src="{{ asset('assets/images/logos/dark-logo.svg') }}" width="180"
                                        alt="">
                                </span>
                                <p class="text-center">@yield('title', 'Fill out the form')</p>
                                @yield('form')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
