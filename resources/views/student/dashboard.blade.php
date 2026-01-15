@extends('layouts.main-student')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
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
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            getQuarterlyGrades: "{{ route('student.dashboard.quarterly-grades') }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection