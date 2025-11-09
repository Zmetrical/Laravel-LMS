@php
    $userType = $userType ?? 'student';
    $isTeacher = $userType === 'teacher';
    $mainLayout = $isTeacher ? 'layouts.main-teacher' : 'layouts.main-student';
    $homeRoute = $isTeacher ? 'teacher.home' : 'student.home';
    $classListRoute = $isTeacher ? 'teacher.list_class' : 'student.list_class';
@endphp

@extends($mainLayout)

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .sticky-top {
            position: sticky;
            top: 70px;
            z-index: 1020;
        }
    </style>
    @yield('page-styles')
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-book-open"></i> Class Management
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route($homeRoute) }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route($classListRoute) }}">Classes</a></li>
                <li class="breadcrumb-item active">{{ $class->class_name ?? 'Class Details' }}</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Course Info Card -->
        <div class="card card-primary card-outline">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-1">
                            <i class="fas fa-graduation-cap text-primary"></i>
                            <span id="className">{{ $class->class_name ?? 'Loading...' }}</span>
                        </h3>
                        <p class="text-muted mb-0">
                            <small>
                                <i class="fas fa-tag"></i> Course Code: <span id="classCode">{{ $class->class_code ?? '' }}</span>
                                | <i class="fas fa-calendar"></i> SY {{ date('Y') }}-{{ date('Y') + 1 }}
                            </small>
                        </p>
                    </div>
                    <div class="col-md-4 text-right">
                        <div class="d-inline-block mr-2">
                            <small class="text-muted d-block">Total Lessons</small>
                            <h3 class="mb-0 text-primary" id="lessonCount">0</h3>
                        </div>
                        <div class="d-inline-block">
                            <small class="text-muted d-block">Total Quizzes</small>
                            <h3 class="mb-0 text-info" id="totalQuizCount">0</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="classTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'lessons' ? 'active' : '' }}" 
                           href="{{ route($isTeacher ? 'teacher.class.lessons' : 'student.class.lessons', $class->id ?? 0) }}">
                            <i class="fas fa-book-open"></i> Lessons
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'quizzes' ? 'active' : '' }}" 
                           href="{{ route($isTeacher ? 'teacher.class.quizzes' : 'student.class.quizzes', $class->id ?? 0) }}">
                            <i class="fas fa-clipboard-list"></i> All Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'grades' ? 'active' : '' }}" 
                           href="{{ route($isTeacher ? 'teacher.class.grades' : 'student.class.grades', $class->id ?? 0) }}">
                            <i class="fas fa-chart-line"></i> Grades
                        </a>
                    </li>
                    @if($isTeacher)
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'participants' ? 'active' : '' }}" 
                           href="{{ route('teacher.class.participants', $class->id ?? 0) }}">
                            <i class="fas fa-users"></i> Participants
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            <div class="card-body">
                @yield('tab-content')
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    
    <script>
        const USER_TYPE = "{{ $userType }}";
        const IS_TEACHER = {{ $isTeacher ? 'true' : 'false' }};
        const CLASS_ID = {{ $class->id ?? 0 }};
        const CSRF_TOKEN = "{{ csrf_token() }}";
    </script>
    
    @yield('page-scripts')
@endsection