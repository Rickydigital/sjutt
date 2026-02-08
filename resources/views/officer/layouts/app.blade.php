<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Student Portal - SJUT</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="{{ asset('app-assets/img/kaiadmin/favicon.ico') }}" type="image/x-icon" />

    {{-- Fonts and Icons --}}
    <link rel="stylesheet" href="{{ asset('app-assets/bootstrap-icons/bootstrap-icons.min.css') }}">

    {{-- CSS Files --}}
    <link rel="stylesheet" href="{{ asset('app-assets/bootstrap-5.0.2/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/css/plugins.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('app-assets/css/kaiadmin.css') }}" />
    <link rel="stylesheet" href="{{ asset('app-assets/css/custom.css') }}" />
    

    {{-- WebFont Loader --}}
    <script src="{{ asset('app-assets/js/plugin/webfont/webfont.min.js') }}"></script>
    <script>
        WebFont.load({
            google: {
                families: ["Public Sans:300,400,500,600,700"]
            },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["{{ asset('app-assets/css/fonts.min.css') }}"],
            },
            active: function() {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <style>


            /* Fix Select2 height to match Bootstrap 5 form-select */
.select2-container .select2-selection--single {
    height: calc(2.25rem + 2px);
    padding: .375rem .75rem;
    border: 1px solid #ced4da;
    border-radius: .375rem;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.5rem;
    padding-left: 0;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(2.25rem + 2px);
}

/* Multiple select */
.select2-container .select2-selection--multiple {
    min-height: calc(2.25rem + 2px);
    border: 1px solid #ced4da;
    border-radius: .375rem;
    padding: .25rem .5rem;
}

/* Ensure dropdown appears above modal */
.select2-container--open {
    z-index: 99999;
}

    </style>
    {{-- Child Styles --}}
    @yield('styles')
</head>

<body>
    <div class="wrapper">
        {{-- Student sidebar (different menu items / permissions) --}}
        @include('officer.partials.sidebar')    {{-- ← change this include --}}

        <div class="main-panel">
            {{-- Student topbar (can show student name, ID, profile pic, notifications, etc.) --}}
            @include('officer.partials.topbar')     {{-- ← change this include --}}

            <div class="container">
                <div class="page-inner">
                    {{-- Here goes all student pages content --}}
                    @yield('content')
                </div>
            </div>

            {{-- Footer (can be same or slightly customized) --}}
            @include('components.app-footer')
        </div>
    </div>

    {{-- Core JS Files --}}
    <script src="{{ asset('app-assets/js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('app-assets/bootstrap-5.0.2/js/bootstrap.bundle.min.js') }}"></script>

    {{-- Plugin JS Files --}}
    <script src="{{ asset('app-assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/plugin/chart.js/chart.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/plugin/chart-circle/circles.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/plugin/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/plugin/jsvectormap/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/plugin/jsvectormap/world.js') }}"></script>
    <script src="{{ asset('app-assets/js/plugin/sweetalert/sweetalert.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="{{ asset('app-assets/js/kaiadmin.min.js') }}"></script>
    <script src="{{ asset('app-assets/select2/js/select2.full.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <script src="{{ asset('app-assets/js/custom.js') }}"></script>

    {{-- Optional: keep example sparkline charts if used in student dashboard --}}
    <script>
        $("#lineChart").sparkline([102, 109, 120, 99, 110, 105, 115], {
            type: "line",
            height: "70",
            width: "100%",
            lineWidth: "2",
            lineColor: "#177dff",
            fillColor: "rgba(23, 125, 255, 0.14)",
        });

        $("#lineChart2").sparkline([99, 125, 122, 105, 110, 124, 115], {
            type: "line",
            height: "70",
            width: "100%",
            lineWidth: "2",
            lineColor: "#f3545d",
            fillColor: "rgba(243, 84, 93, .14)",
        });

        $("#lineChart3").sparkline([105, 103, 123, 100, 95, 105, 115], {
            type: "line",
            height: "70",
            width: "100%",
            lineWidth: "2",
            lineColor: "#ffa534",
            fillColor: "rgba(255, 165, 52, .14)",
        });

    </script>

    {{-- Global success / error notifications (same style as main layout) --}}
    @if (session('success'))
        <script>
            $.notify({
                icon: 'icon-bell',
                title: 'Success',
                message: '{{ session('success') }}',
            }, {
                type: 'success',
                placement: {
                    from: "top",
                    align: "right"
                },
                time: 4000,
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            $.notify({
                icon: 'bi bi-ban',
                title: 'Failed',
                message: '{{ session('error') }}',
            }, {
                type: 'danger',
                placement: {
                    from: "top",
                    align: "right"
                },
                time: 5000,
            });
        </script>
    @endif

    {{-- Child Scripts --}}
    @yield('scripts')
</body>
</html>