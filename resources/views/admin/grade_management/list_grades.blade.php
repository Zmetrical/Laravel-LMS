@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .filter-card { 
            background: #f8f9fa; 
            border: 1px solid #e9ecef; 
        }
        .filter-card .form-control, .filter-card .form-select { 
            font-size: 0.875rem; 
            height: calc(2.25rem + 2px);
        }
        .filter-card label { 
            font-size: 0.75rem; 
            font-weight: 600; 
            color: #6c757d; 
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }
        .badge-passed { 
            background-color: #28a745; 
            color: white; 
        }
        .badge-failed { 
            background-color: #dc3545; 
            color: white; 
        }
        .badge-inc { 
            background-color: #ffc107; 
            color: #212529; 
        }
        .badge-drp, .badge-w { 
            background-color: #6c757d; 
            color: white; 
        }
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem + 2px) !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px) !important;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Student Grades</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label>Search Student</label>
                    <input type="text" class="form-control" id="searchStudent" placeholder="Number or Name...">
                </div>
                <div class="col-md-2">
                    <label>Semester</label>
                    <select class="form-control" id="semester">
                        <option value="">All Semesters</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" 
                                {{ isset($activeSemester) && $activeSemester->semester_id == $sem->id ? 'selected' : '' }}>
                                {{ $sem->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Class/Subject</label>
                    <select class="form-control" id="classFilter" style="width: 100%;">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->class_code }}">{{ $class->class_code }} - {{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Section</label>
                    <select class="form-control" id="sectionFilter" style="width: 100%;">
                        <option value="">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_code }}">{{ $section->section_code }} - {{ $section->section_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select class="form-control" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="PASSED">Passed</option>
                        <option value="FAILED">Failed</option>
                        <option value="INC">INC</option>
                        <option value="DRP">DRP</option>
                        <option value="W">W</option>
                    </select>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12 text-right">
                    <button class="btn btn-secondary" id="clearFilters" title="Clear Filters">
                        <i class="fas fa-undo"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Grades Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-graduation-cap mr-2"></i>Grade Records</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="gradesCount">{{ count($grades) }} Records</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="gradesTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Semester</th>
                            <th class="text-center">Final Grade</th>
                            <th class="text-center">Remarks</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grades as $grade)
                            <tr>
                                <td>{{ $grade->student_number }}</td>
                                <td>{{ $grade->last_name }}, {{ $grade->first_name }}</td>
                                <td data-class-code="{{ $grade->class_code }}">{{ $grade->class_name }}</td>
                                <td data-section-code="{{ $grade->section_code }}">{{ $grade->section_name ?? 'N/A' }}</td>
                                <td data-semester-id="{{ $grade->semester_id }}">{{ $grade->semester_display }}</td>
                                <td class="text-center"><strong>{{ $grade->final_grade }}</strong></td>
                                <td class="text-center">
                                    <span class="badge badge-{{ strtolower($grade->remarks) }}">
                                        {{ $grade->remarks }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-secondary view-details-btn" 
                                            data-grade-id="{{ $grade->id }}"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Grade Details Modal -->
<div class="modal fade" id="gradeDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar"></i> Grade Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Student Info -->
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Student Number:</th>
                                <td id="detailStudentNumber"></td>
                            </tr>
                            <tr>
                                <th>Student Name:</th>
                                <td id="detailStudentName"></td>
                            </tr>
                            <tr>
                                <th>Section:</th>
                                <td id="detailSection"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Class:</th>
                                <td id="detailClass"></td>
                            </tr>
                            <tr>
                                <th>Semester:</th>
                                <td id="detailSemester"></td>
                            </tr>
                            <tr>
                                <th>Computed By:</th>
                                <td id="detailComputedBy"></td>
                            </tr>
                            <tr>
                                <th>Computed At:</th>
                                <td id="detailComputedAt"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <!-- Grade Breakdown -->
                <h6 class="mb-3"><i class="fas fa-calculator"></i> Quarter Grades</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Quarter 1</h6>
                                <h2 id="detailQ1">0.00</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Quarter 2</h6>
                                <h2 id="detailQ2">0.00</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Final Grade</h6>
                                <h2 id="detailFinalGrade">0.00</h2>
                                <h5 id="detailRemarks"></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            getGradeDetails: "{{ route('admin.grades.details', ['id' => ':id']) }}"
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection