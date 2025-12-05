@extends('layouts.main-student')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <style>
        .info-box {
            min-height: 90px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .info-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .quiz-card {
            transition: all 0.2s;
            border-left: 3px solid #007bff;
        }
        .quiz-card:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .quiz-card.urgent {
            border-left-color: #dc3545;
        }
        .grade-item {
            padding: 10px;
            border-left: 3px solid #6c757d;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        .grade-item.excellent {
            border-left-color: #28a745;
        }
        .grade-item.good {
            border-left-color: #007bff;
        }
        .grade-item.needs-improvement {
            border-left-color: #ffc107;
        }
        .grade-item.poor {
            border-left-color: #dc3545;
        }
        @media (max-width: 768px) {
            .info-box {
                margin-bottom: 1rem;
            }
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Active Semester Info -->
    <div class="alert alert-dark alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h5>
            <i class="fas fa-calendar-alt"></i> 
            {{ $activeSemesterDisplay ?? 'No Active Semester' }}
        </h5>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-book"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Enrolled Classes</span>
                    <span class="info-box-number" id="enrolledClassesCount">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-secondary"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Completed Lessons</span>
                    <span class="info-box-number" id="completedLessonsCount">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-clipboard-list"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Quizzes</span>
                    <span class="info-box-number" id="pendingQuizzesCount">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-secondary"><i class="fas fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Average Grade</span>
                    <span class="info-box-number" id="averageGrade">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Available Quizzes -->
        <div class="col-lg-6 col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clipboard-check"></i> Available Quizzes
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" id="refreshQuizzes">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <div id="quizzesContainer">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="text-muted mt-2">Loading quizzes...</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="#" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> View All Quizzes
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Grades -->
        <div class="col-lg-6 col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-star"></i> Recent Grades
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" id="refreshGrades">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <div id="recentGradesContainer">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-secondary"></i>
                            <p class="text-muted mt-2">Loading grades...</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('student.list_grade') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-chart-bar"></i> View All Grades
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-area"></i> Performance Overview
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" id="refreshChart">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mt-4">
        <div class="col-lg-3 col-md-6 col-12">
            <a href="{{ route('student.list_class') }}" class="text-decoration-none">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><i class="fas fa-book-reader"></i></h3>
                        <p>My Classes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-lg-3 col-md-6 col-12">
            <a href="#" class="text-decoration-none">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><i class="fas fa-clipboard-list"></i></h3>
                        <p>My Quizzes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-lg-3 col-md-6 col-12">
            <a href="{{ route('student.list_grade') }}" class="text-decoration-none">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><i class="fas fa-chart-bar"></i></h3>
                        <p>My Grades</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-lg-3 col-md-6 col-12">
            <a href="#" class="text-decoration-none">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><i class="fas fa-user"></i></h3>
                        <p>My Profile</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        const API_ROUTES = {
            getStats: "{{ route('student.dashboard.stats') }}",
            getQuizzes: "{{ route('student.dashboard.quizzes') }}",
            getRecentGrades: "{{ route('student.dashboard.recent-grades') }}",
            getPerformanceChart: "{{ route('student.dashboard.performance') }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection