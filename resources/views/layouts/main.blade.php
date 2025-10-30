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
    </style>
@endsection

@section('body')

    <body class="hold-transition sidebar-mini layout-fixed">
        <div class="wrapper">

            <!-- Navbar -->
            <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                                class="fas fa-bars"></i></a>
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
            <aside class="main-sidebar sidebar-dark-primary elevation-4">
                <a href="{{ route('admin.home') }}" class="brand-link text-center" style="padding: 20px 0;">
                    <img src="{{ asset('img/logo/trinity_logo.png') }}" alt="Trinity Logo"
                        style="width: 80px; height: 80px; display: block; margin: 0 auto;">
                    <span class="brand-text font-weight-light d-block mt-2">Admin</span>
                </a>
                <div class="sidebar">
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                            data-accordion="false">
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
                                        <a href="{{ route('admin.create_student') }}" class="nav-link {{ Request::routeIs('admin.create_student') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Insert Student</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.create_teacher') }}" class="nav-link {{ Request::routeIs('admin.create_teacher') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Insert Teacher</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_student') }}" class="nav-link {{ Request::routeIs('admin.list_student') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List Students</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_teacher') }}" class="nav-link {{ Request::routeIs('admin.list_teacher') ? 'active' : '' }}">
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
                                        <a href="{{ route('admin.list_class') }}" class="nav-link {{ Request::routeIs('admin.list_class') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List Class</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_strand') }}" class="nav-link {{ Request::routeIs('admin.list_strand') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List Strand</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_section') }}" class="nav-link {{ Request::routeIs('admin.list_section') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List Section</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.list_schoolyear') }}" class="nav-link {{ Request::routeIs('admin.list_schoolyear') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>List School Year</p>
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
        $(document).ready(function() {
            // Restore sidebar state from localStorage
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                $('body').addClass('sidebar-collapse');
            }

            // Save sidebar state when toggled
            $('[data-widget="pushmenu"]').on('collapsed.lte.pushmenu', function() {
                localStorage.setItem('sidebar-collapsed', 'true');
            });

            $('[data-widget="pushmenu"]').on('shown.lte.pushmenu', function() {
                localStorage.setItem('sidebar-collapsed', 'false');
            });
        });
    </script>
    @yield('scripts')
@endsection