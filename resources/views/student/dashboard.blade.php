@extends('layouts.main-student')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        
        .grade-excellent { color: #28a745; font-weight: bold; }
        .grade-very-good { color: #007bff; font-weight: bold; }
        .grade-good { color: #17a2b8; font-weight: bold; }
        .grade-fair { color: #ffc107; font-weight: bold; }
        .grade-poor { color: #dc3545; font-weight: bold; }
        .grade-pending { color: #6c757d; font-style: italic; }
        
        .table-grades th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .table-grades td {
            vertical-align: middle;
        }
        
        .final-grade-col {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 1.1em;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item active">Performance Overview</li>
    </ol>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Active Semester Info -->
    <div class="alert alert-dark alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h5>
            <i class="fas fa-calendar-alt"></i> 
            {{ $activeSemesterDisplay ?? 'No Active Semester' }}
        </h5>
    </div>

    <!-- Detailed Grade Report -->
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table"></i> Detailed Grade Report
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" id="refreshGrades">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <div id="gradesTableContainer">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="text-muted mt-2">Loading grades...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Grade Component Breakdown Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i> Grade Component Breakdown
                    </h3>
                    <div class="card-tools">
                        <div class="btn-group btn-group-sm mr-2" id="quarterToggle">
                            <!-- Buttons will be populated dynamically -->
                        </div>
                        <button type="button" class="btn btn-tool" id="refreshBreakdown">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="breakdownChartContainer">
                        <canvas id="gradeBreakdownChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Semester Summary -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i> Semester Summary
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" id="refreshSummary">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <div id="semesterSummary">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-secondary"></i>
                            <p class="text-muted mt-2">Loading summary...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Legend -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-2"><strong>Grade Legend:</strong></h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small>
                                <span class="grade-excellent">● 90-100</span> - Outstanding<br>
                                <span class="grade-very-good">● 85-89</span> - Very Satisfactory<br>
                                <span class="grade-good">● 80-84</span> - Satisfactory
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small>
                                <span class="grade-fair">● 75-79</span> - Fairly Satisfactory<br>
                                <span class="grade-poor">● Below 75</span> - Did Not Meet Expectations<br>
                                <span class="grade-pending">● N/A</span> - Not Yet Available
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            getQuarterlyGrades: "{{ route('student.dashboard.quarterly-grades') }}",
            getGradeBreakdown: "{{ route('student.dashboard.grade-breakdown') }}",
            getSemesterSummary: "{{ route('student.dashboard.semester-summary') }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection