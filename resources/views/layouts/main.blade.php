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
    <body class="hold-transition sidebar-mini layout-fixed">
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
                    <li class="nav-item dropdown">
                        <i class="fas fa-user mr-2"></i> Admin
                    </li>
                </ul>
            </nav>

            <!-- Main Sidebar -->
            <aside class="main-sidebar sidebar-dark-primary elevation-4">
                <a href="{{ route('admin.home') }}" class="brand-link text-center" style="padding: 20px 0;">
                    <img src="{{ asset('img/logo/trinity_logo.png') }}" alt="Trinity Logo"
                        style="width: 150px; height: 150px; display: block; margin: 0 auto;">
                </a>
                <!-- School Year -->
                <div class="user-panel py-3 px-3 d-flex flex-column">
                    <div class="info w-100">
                        <div class="d-flex justify-content-between align-items-start">
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
                            <div class="ml-2">
                                <a href="{{ route('admin.schoolyears.index') }}" class="text-white-50" title="Manage School Years">
                                    <i class="fas fa-cog"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sidebar">

                    <!-- Navigation Menu -->
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                            <!-- School Year Specific Section -->
                            <li class="nav-section-title">Semester Operation</li>
                            
                            <li class="nav-item {{ Request::is('enrollment_management/*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ Request::is('enrollment_management/*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-graduate"></i>
                                    <p>
                                        Enrollment
                                    </p>
                                     <i class="right fas fa-angle-left"></i>
                                </a>
                                    <ul class="nav nav-treeview">
                                        <li class="nav-item">
                                            <a href="{{ route('admin.enroll_class') }}" class="nav-link {{ Request::routeIs('admin.enroll_class') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                                <p>Class Enrollment</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('admin.section_class_enrollment') }}" class="nav-link {{ Request::routeIs('admin.section_class_enrollment') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                                <p>Section Enrollment</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('admin.student_irreg_class_enrollment') }}" class="nav-link {{ Request::routeIs('admin.student_irreg_class_enrollment') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                                <p>Student Enrollment</p>
                                            </a>
                                        </li>
                                    </ul>
                            </li>

                            <li class="nav-item {{ Request::is('grades/*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ Request::is('grades/*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-graduate"></i>
                                    <p>
                                        Grades
                                    </p>
                                     <i class="right fas fa-angle-left"></i>
                                </a>
                                    <ul class="nav nav-treeview">
                                        <li class="nav-item">
                                            <a href="{{ route('admin.grades.list') }}" class="nav-link {{ Request::routeIs('admin.enroll_class') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                                <p>Grades</p>
                                            </a>
                                        </li>
                                    </ul>
                            </li>

                            <div class="nav-divider"></div>

                            <!-- Universal Section -->
                            <li class="nav-section-title">System Operation</li>
                            
                            <!-- User Menu -->
                            <li class="nav-item {{ Request::is('user_management/*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ Request::is('user_management/*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>
                                        User Management
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.create_student') }}"
                                            class="nav-link {{ Request::routeIs('admin.create_student') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Insert Student</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.create_teacher') }}"
                                            class="nav-link {{ Request::routeIs('admin.create_teacher') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Insert Teacher</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_student') }}"
                                            class="nav-link {{ Request::routeIs('admin.list_student') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List Students</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_teacher') }}"
                                            class="nav-link {{ Request::routeIs('admin.list_teacher') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List Teachers</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Class Menu -->
                            <li class="nav-item {{ Request::is('class_management/*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ Request::is('class_management/*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chalkboard-teacher"></i>
                                    <p>
                                        Class Management
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_class') }}"
                                            class="nav-link {{ Request::routeIs('admin.list_class') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List Class</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_strand') }}"
                                            class="nav-link {{ Request::routeIs('admin.list_strand') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List Strand</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_section') }}"
                                            class="nav-link {{ Request::routeIs('admin.list_section') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List Section</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                        </ul>
                    </nav>
                </div>

                <!-- Logout Button at Bottom -->
                <div class="sidebar-bottom p-3">
                    <a href="{{ route('admin.login') }}" class="btn btn-danger btn-block">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </aside>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
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
        $(document).ready(function () {
            // Sidebar collapse persistence
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                $('body').addClass('sidebar-collapse');
            }

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