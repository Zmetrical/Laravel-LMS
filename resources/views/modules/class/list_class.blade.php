@php
    $userType = $userType ?? 'student'; // Default to student if not set
    $isTeacher = $userType === 'teacher';
    $homeRoute = $isTeacher ? 'teacher.home' : 'student.home';
    $mainLayout = $isTeacher ? 'layouts.main-teacher' : 'layouts.main-student';
    $icon = $isTeacher ? 'fas fa-chalkboard-teacher' : 'fas fa-book';
    $emptyIcon = $isTeacher ? 'fas fa-chalkboard-teacher' : 'fas fa-book-open';
    $emptyTitle = $isTeacher ? 'No Classes Assigned' : 'No Classes Yet';
    $emptyMessage = $isTeacher ? "You don't have any classes assigned yet." : 'You are not enrolled in any classes at the moment.';
@endphp

@extends($mainLayout)

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="{{ $icon }}"></i> My Classes
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route($homeRoute) }}">Home</a></li>
                <li class="breadcrumb-item active">My Classes</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading your classes...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" style="display: none;">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="{{ $emptyIcon }} fa-4x text-muted mb-3"></i>
                    <h4>{{ $emptyTitle }}</h4>
                    <p class="text-muted">{{ $emptyMessage }}</p>
                </div>
            </div>
        </div>

        <!-- Classes Grid (Cards) - Both Teacher and Student -->
        <div id="classesGrid" class="row" style="display: none;"></div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    
    <script>
        const USER_TYPE = "{{ $userType }}";
        const API_ROUTES = {
            getClasses: "{{ $isTeacher ? route('teacher.classes.list') : route('student.classes.list') }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection