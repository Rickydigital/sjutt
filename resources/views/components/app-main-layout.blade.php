<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>SJUT</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="{{ asset('app-assets/img/kaiadmin/favicon.ico') }}" type="image/x-icon" />

    {{-- Fonts and Icons --}}
    <link rel="stylesheet" href="{{ asset('app-assets/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- CSS Files --}}
    <link rel="stylesheet" href="{{ asset('app-assets/bootstrap-5.0.2/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/css/plugins.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('app-assets/css/kaiadmin.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('app-assets/css/custom.css') }}" />

    {{-- Custom Styles for Timetable --}}
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Public Sans', sans-serif;
        }
        .main-panel {
            background-color: #f4f6f9;
        }
        .page-inner {
            padding: 20px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6f42c1, #4B2E83);
            border: none;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #4B2E83, #6f42c1);
        }
        .select2-container--classic .select2-selection--single,
        .select2-container--classic .select2-selection--multiple {
            border-radius: 8px;
            border: 1px solid #ced4da;
        }
        .select2-container--classic .select2-selection--single:focus,
        .select2-container--classic .select2-selection--multiple:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 5px rgba(111, 66, 193, 0.5);
        }
    </style>

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

    {{-- Child Styles --}}
    @yield('styles')
</head>

<body>
    <div class="wrapper">
        @include('components.app-side-bar')

        <div class="main-panel">
            @include('components.app-top-bar')

            <div class="container">
                <div class="page-inner">
                    @yield('content')
                </div>
            </div>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js" integrity="sha512-CryKbMe7sjSCDPl18jtJI5DR5jtkUWxPXWaLCst6QjH8wxDexfRJic2WRmRXmstr2Y8SxDDWuBO6CQC6IE4KtaA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('app-assets/js/custom.js') }}"></script>

    {{-- Sparkline Charts --}}
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

    {{-- Success Notification --}}
    @session('success')
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
                time: 1000,
            });
        </script>
    @endsession

    {{-- Error Notification --}}
    @session('error')
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
                time: 1000,
            });
        </script>
    @endsession

    {{-- Child Scripts --}}
    @yield('scripts')
</body>

</html>