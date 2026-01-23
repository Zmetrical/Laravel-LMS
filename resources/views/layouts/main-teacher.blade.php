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

        .nav-sidebar>.nav-item.menu-open>.nav-link,
        .nav-sidebar>.nav-item>.nav-link.active {
            background-color: #4b545c !important;
            color: #fff !important;
        }

        .nav-treeview>.nav-item>.nav-link.active {
            background-color: #fff !important;
            color: #343a40 !important;
        }

        /* Google Classroom style breadcrumb */
        .main-header.navbar {
            border-bottom: 1px solid #dee2e6;
        }

        .breadcrumb-custom {
            background-color: transparent;
            padding: 0;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .breadcrumb-custom .breadcrumb-item {
            font-size: 20px;
            font-weight: 400;
            color: #202124;
        }

        .breadcrumb-custom .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            font-size: 20px;
            color: #5f6368;
            padding: 0 8px;
        }

        .breadcrumb-custom .breadcrumb-item a {
            color: #5f6368;
            text-decoration: none;
        }

        .breadcrumb-custom .breadcrumb-item a:hover {
            color: #202124;
            text-decoration: underline;
        }

        .breadcrumb-custom .breadcrumb-item.active {
            color: #202124;
            font-weight: 500;
        }

        .breadcrumb-icon {
            font-size: 24px;
            color: #5f6368;
            margin-right: 12px;
        }

        .content-wrapper {
            padding-top: 0;
        }

        .content-header {
            padding: 0;
            margin-bottom: 20px;
        }

        .nav-section-title {
            padding: 10px 15px 5px 15px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            font-weight: 600;
        }

        .nav-divider {
            height: 1px;
            background-color: rgba(255,255,255,0.1);
            margin: 10px 15px;
        }
    </style>
@endsection

@section('body')

    <body class="hold-transition layout-fixed">
        <div class="wrapper">

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Breadcrumb in Navbar -->
    <ul class="navbar-nav ml-3 flex-grow-1">
        <li class="nav-item d-flex align-items-center">
            @yield('breadcrumb')
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a href="{{ route('teacher.profile') }}" class="nav-link">
                <i class="fas fa-user-tie mr-2"></i>
                {{ Auth::guard('teacher')->user()->first_name ?? 'Teacher' }} {{ Auth::guard('teacher')->user()->last_name ?? '' }}
            </a>
        </li>
    </ul>
</nav>


            <!-- Main Sidebar -->
            <aside class="main-sidebar sidebar-dark-primary  elevation-4">
                <a href="{{ route('teacher.home') }}" class="brand-link text-center" style="padding: 20px 0;">
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

                                <div class="user-panel py-3 px-3 d-flex flex-column">
                    <div class="info w-100">
                        <div class="d-flex justify-content-between">
                            <div class="text-white font-weight-bold mb-1" style="font-size: 20px;">
                            Teacher
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sidebar">
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                            data-accordion="false">
                            <li class="nav-item">
                                <a href="{{ route('teacher.list_class') }}"
                                    class="nav-link {{ Request::routeIs('teacher.list_class') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-th-list"></i>                                    
                                <p>My Classes</p>
                                </a>
                            </li>

                                <li class="nav-item">
                                <a href="{{ route('teacher.adviser.grade.cards') }}"
                                    class="nav-link {{ Request::routeIs('teacher.adviser.grade.cards') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-th-list"></i>      
                                    <p>My Students</p>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <!-- Logout Button at Bottom -->
                <div class="sidebar-bottom p-3">
                    <form method="POST" action="{{ route('teacher.logout') }}" id="teacher-logout-form">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-block">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <br>
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