@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .submission-progress {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .submission-progress-bar {
            height: 100%;
            transition: width 0.3s;
        }

        .student-card {
            border-left: 4px solid #6c757d;
            transition: all 0.2s;
        }
        .student-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .class-card {
            border-left: 4px solid #6c757d;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Irregular Grade View</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">

    <div class="alert alert-dark">
        <h5><i class="icon fas fa-calendar-alt"></i> {{ $activeSemesterDisplay }}</h5>
    </div>

    <div class="row">
        <!-- Left sidebar — student list -->
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-graduate"></i> Irregular Students</h3>
                    <div class="card-tools">
                        <span class="badge badge-light" id="studentListCount">0 Students</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Search -->
                    <div class="p-3">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="studentSearch"
                                   placeholder="Search by name or student number...">
                        </div>
                    </div>
                    <!-- Status filter -->
                    <div class="px-3 pb-3">
                        <select class="form-control form-control-sm" id="submissionFilter">
                            <option value="">All Students</option>
                            <option value="complete">Fully Submitted</option>
                            <option value="partial">Partially Submitted</option>
                            <option value="none">No Submission</option>
                        </select>
                    </div>

                    <div id="studentListContainer"
                         style="max-height: 600px; overflow-y: auto;">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2 mb-0 small">Loading students...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel — student details -->
        <div class="col-md-8">
            <!-- Placeholder -->
            <div id="noStudentSelected" class="card card-primary card-outline">
                <div class="card-body text-center py-5">
                    <i class="fas fa-arrow-left fa-3x text-muted mb-3"></i>
                    <h5>No Student Selected</h5>
                    <p class="text-muted">Select an irregular student from the left panel to view their grade submission status.</p>
                </div>
            </div>

            <!-- Details panel -->
            <div id="studentDetails" style="display: none;">

                <!-- Student info header -->
                <div class="card card-primary">
                    <div class="card-body">
                        <h4 class="mb-1" id="detailStudentName"></h4>
                        <p class="text-muted mb-0" id="detailStudentMeta"></p>
                    </div>
                </div>

                <!-- Enrolled classes with grade status -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Grade Submission Status</h3>
                        <div class="card-tools">
                            <span class="badge badge-light" id="classesCount">0 Classes</span>
                        </div>
                    </div>
                    <div class="card-body" id="classesContainer">
                        <div class="text-center py-4 text-primary">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
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
            getStudents:      "{{ route('admin.irreg.grades-list') }}",
            getStudentDetails: "{{ route('admin.irreg.grades-details', ['student_number' => ':sn']) }}",
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection