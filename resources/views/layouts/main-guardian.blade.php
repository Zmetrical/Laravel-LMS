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

        .student-nav-item {
            background-color: rgba(255,255,255,0.05);
            margin: 5px 10px;
            border-radius: 5px;
        }

        .student-nav-item:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .student-nav-item .nav-link {
            padding: 10px 15px;
        }

        .student-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
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
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user-shield mr-2"></i>
                            {{ session('guardian_name', 'Guardian') }}
                        </span>
                    </li>
                </ul>
            </nav>

            <!-- Main Sidebar -->
            <aside class="main-sidebar sidebar-dark-primary elevation-4">
                <a href="{{ route('guardian.home') }}" class="brand-link text-center" style="padding: 20px 0;">
                    <img src="{{ asset('img/logo/trinity_logo.png') }}" alt="Trinity Logo"
                        style="width: 150px; height: 150px; display: block; margin: 0 auto;">
                </a>

                <!-- School Year Info -->
                <div class="user-panel py-3 px-3 d-flex flex-column">
                    <div class="info w-100">
                        <div class="d-flex justify-content-center align-items-start">
                            <div class="flex-grow-1">
                                <div class="text-white font-weight-bold mb-1" style="font-size: 14px;">
                                    @if($activeSemester)
                                        SY {{ $activeSemester->school_year_code }}
                                    @else
                                        <i class="fas fa-exclamation-triangle mr-2"></i>No Active Semester
                                    @endif
                                </div>
                                <div class="text-white-50 small">
                                    {{ $activeSemester->semester_name ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="user-panel py-3 px-3 d-flex flex-column">
                    <div class="info w-100">
                        <div class="d-flex justify-content-between">
                            <div class="text-white font-weight-bold mb-1" style="font-size: 20px;">
                            Guardian
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sidebar">
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                            data-accordion="false">
                            
                            <!-- Dashboard -->
                            <li class="nav-item">
                                <a href="{{ route('guardian.home') }}"
                                    class="nav-link {{ Request::routeIs('guardian.home') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-home"></i>
                                    <p>Home</p>
                                </a>
                            </li>

                            @php
                                $guardianId = session('guardian_id');
                                $students = DB::table('guardian_students as gs')
                                    ->join('students as s', 'gs.student_number', '=', 's.student_number')
                                    ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                                    ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
                                    ->where('gs.guardian_id', $guardianId)
                                    ->select(
                                        's.student_number',
                                        's.first_name',
                                        's.last_name',
                                        's.profile_image',
                                        'l.name as level_name'
                                    )
                                    ->orderBy('s.first_name')
                                    ->get();
                            @endphp

                            @if($students->count() > 0)

                                @foreach($students as $student)
                                    <li class="nav-item student-nav-item">
                                        <a href="{{ route('guardian.student.grades', $student->student_number) }}"
                                            class="nav-link {{ Request::is('guardian/student/' . $student->student_number . '*') ? 'active' : '' }}">
                                            <img src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}" 
                                                 alt="{{ $student->first_name }}" 
                                                 class="student-avatar">
                                            <span>{{ $student->first_name }} {{ $student->last_name }}</span>
                                        </a>
                                    </li>
                                @endforeach

                            @endif
                        </ul>
                    </nav>
                </div>

                <!-- Info Section at Bottom -->
                <div class="sidebar-bottom p-3">
                    <div class="text-white-50 small text-center">
                    </div>
                </div>
            </aside>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <br>
                <section class="content">
                    <div class="container-fluid">
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
                            </div>
                        @endif
                        
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </section>
            </div>
        </div>
    </body>
@endsection

@section('foot')
    <script>
        $(document).ready(function() {
            // Keep sidebar state persistent
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                $('body').addClass('sidebar-collapse');
            }

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