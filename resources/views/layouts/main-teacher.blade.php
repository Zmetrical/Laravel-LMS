@extends('layouts.root')


@section('head')
    @yield('styles')
    <style>
        .sidebar-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #343a40;
            border-top: 1px solid #4b545c;
        }

        .sidebar {
            padding-bottom: 80px;
        }

        /* Optional: active item style */
        .nav-sidebar>.nav-item.menu-open>.nav-link,
        .nav-sidebar>.nav-item>.nav-link.active {
            background-color: #4b545c !important;
            /* Blue header color */
            color: #fff !important;
        }


        /* Optional: active sub-item style */
        .nav-treeview>.nav-item>.nav-link.active {
            background-color: #fff !important;
            color: #343a40 !important;
        }
    </style>
@endsection

@section('body')

    <body class="hold-transition sidebar-mini layout-fixed">
        <div class="wrapper">

            <!-- Navbar -->
            <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                    </li>
                </ul>

                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link" data-toggle="dropdown" href="#">
                            <i class="far fa-user"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                            <a href="#" class="dropdown-item">Profile</a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>


            <!-- Main Sidebar -->
            <aside class="main-sidebar sidebar-dark-primary  elevation-4">
                <a href="{{ route('admin.home') }}" class="brand-link text-center" style="padding: 20px 0;">
                    <img src="{{ asset('img/logo/trinity_logo.png') }}" alt="Trinity Logo"
                        style="width: 150px; height: 150px; display: block; margin: 0 auto;">
                </a>
                <!-- School Year -->
                <div class="user-panel py-3 px-3 d-flex flex-column">
                    <div class="info w-100">
                        <div class="d-flex justify-content-center align-items-start">
                            <div class="flex-grow-1">
                                <div class="text-white font-weight-bold mb-1" style="font-size: 14px;">
                                    @if($activeSemester)
                                        </i>SY {{ $activeSemester->school_year_code }}
                                    @else
                                        <i class="fas fa-exclamation-triangle mr-2"></i>No Active Semester</span>
                                    @endif
                                </div>
                                <div class="text-white-50 small">
                                    </i>{{ $activeSemester->semester_name ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sidebar">
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                            data-accordion="false">
                            <!-- User Menu -->
                            <li class="nav-item {{ Request::is('class/*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ Request::is('class/*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>
                                        Class
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('teacher.list_class') }}"
                                            class="nav-link {{ Request::routeIs('teacher.list_class') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Class List</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                        </ul>
                    </nav>
                </div>

                <!-- Logout Button at Bottom -->
                <div class="sidebar-bottom p-3">
                    <a href="{{ route('teacher.login') }}" class="btn btn-danger btn-block">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </aside>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        @yield('breadcrumb')
                    </div>
                </div>

                <section class="content">
                    <div class="container-fluid">
                        @yield('content')
                    </div>
                </section>
            </div>
        </div>
    </body>

@endsection

@section('foot')
    <script>
        // Keep sidebar state persistent across pages
        $(document).ready(function () {
            // Restore sidebar state from localStorage
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                $('body').addClass('sidebar-collapse');
            }

            // Save sidebar state when toggled
            $('[data-widget="pushmenu"]').on('collapsed.lte.pushmenu', function () {
                localStorage.setItem('sidebar-collapsed', 'true');
            });

            $('[data-widget="pushmenu"]').on('shown.lte.pushmenu', function () {
                localStorage.setItem('sidebar-collapsed', 'false');
            });
        });
    </script>
    @yield('scripts')
@endsection