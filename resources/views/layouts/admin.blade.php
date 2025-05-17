<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SJUT') }}</title>
    <meta name="description" content="Sjut community">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" href="https://i.imgur.com/QRAUqs9.png">
    <link rel="shortcut icon" href="https://i.imgur.com/QRAUqs9.png">

    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('app-view/assets/css/cs-skin-elastic.css') }}">
    <link rel="stylesheet" href="{{ asset('app-view/assets/css/style.css') }}">

    <style>
        .select2-container {
            z-index: 1050 !important;
        }
        .select2-dropdown {
            z-index: 1051 !important;
        }
    </style>
</head>
<body>
    <!-- Left Panel -->
    @include('partials.sidebar')
    <!-- /#left-panel -->

    <!-- Right Panel -->
    <div id="right-panel" class="right-panel">
        <!-- Header -->
        @include('partials.topbar')
        <!-- /#header -->

        <!-- Content -->
        <div class="content">
            @yield('content')
        </div>
        <!-- /.content -->

        <div class="clearfix"></div>
    </div>
    <!-- /#right-panel -->

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Avoid jQuery conflicts
        var $j = jQuery.noConflict();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('app-view/assets/js/main.js') }}">
        
    </script>

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

    @yield('scripts')
</body>
</html>