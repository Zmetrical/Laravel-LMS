<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Trinity LMS')</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo/trinity_logo.png') }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- AdminLTE Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

    <style>
        :root {
            --trinity-blue: #2a347e;
        }

        /* Use the variable */
        .bg-primary,
        .btn-primary{
            background-color: var(--trinity-blue) !important;
            border-color: var(--trinity-blue) !important;
        }
        .btn-outline-primary{
            border-color: var(--trinity-blue) !important;
            color: var(--trinity-blue) !important;
        }
        .btn-outline-primary:hover{
            border-color: var(--trinity-blue) !important;

            background-color: var(--trinity-blue) !important;
            color: #fff!important;
        }
        .text-primary{
            color: var(--trinity-blue) !important;
        }
    .question-nav-btn.answered {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
        color: white !important;
    }
    .question-nav-btn.active {
        background-color: var(--trinity-blue) !important;
        border-color: var(--trinity-blue) !important;
        color: white !important;
    }



        .list-group-item.active {
            background-color: var(--trinity-blue) !important;
            border-color: var(--trinity-blue) !important;
        }
        .nav-tabs > .nav-item > .nav-link{
            color: var(--trinity-blue) !important;
        }
        .nav-tabs > .nav-item > .nav-link.active{
            background-color: var(--trinity-blue) !important;
            color: #fff !important;
        }
        .card-primary:not(.card-outline) > .card-header {
            background-color: var(--trinity-blue);
        }


        .card-primary.card-outline {
            border-top: 3px solid var(--trinity-blue) !important;
        }
        .card-primary.card-outline-tabs>.card-header a.active {
            border-top: 3px solid var(--trinity-blue) !important;
        }


    </style>
    <!-- Styles -->
    @yield('head')
</head>

@yield('body')

<!-- jQuery -->
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI -->
<script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE -->
<script src="{{ asset('dist/js/adminlte.min.js') }}"></script>
    <!-- Script -->
    @yield('foot')
</html>
