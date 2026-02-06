@extends('layouts.main-teacher')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <style>
        .filter-card { 
            background: #f8f9fa; 
            border: 1px solid #e9ecef; 
        }
        .filter-card .form-control { 
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
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('teacher.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Student Activity Logs</li>
    </ol>
@endsection

@section('content')
<div class="container-fluid">

    <!-- Filter Card -->
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label>Student</label>
                    <select class="form-control" id="studentFilter">
                        <option value="">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->student_number }}">
                                {{ $student->last_name }}, {{ $student->first_name }} ({{ $student->section_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Action</label>
                    <select class="form-control" id="actionFilter">
                        <option value="">All Actions</option>
                        <option value="viewed">Viewed</option>
                        <option value="submitted">Submitted</option>
                        <option value="attempted">Attempted</option>
                        <option value="completed">Completed</option>
                        <option value="updated">Updated</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Module</label>
                    <select class="form-control" id="moduleFilter">
                        <option value="">All Modules</option>
                        <option value="quizzes">Quizzes</option>
                        <option value="lessons">Lessons</option>
                        <option value="lectures">Lectures</option>
                        <option value="grades">Grades</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Date From</label>
                    <input type="date" class="form-control" id="dateFrom">
                </div>
                <div class="col-md-2">
                    <label>Date To</label>
                    <input type="date" class="form-control" id="dateTo">
                </div>
                <div class="col-md-1 d-flex">
                    <button class="btn btn-secondary btn-block" id="clearFilters" title="Clear Filters">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Audit Logs Table -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-graduate mr-2"></i>Student Activity Logs</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="logsCount">0 Logs</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="studentAuditTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Student</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Record ID</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th class="text-center" style="width: 70px;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-list mr-2"></i>Log Details</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered mb-0">
                    <tr>
                        <th width="140">Timestamp</th>
                        <td id="detail-timestamp"></td>
                    </tr>
                    <tr>
                        <th>Student</th>
                        <td id="detail-student"></td>
                    </tr>
                    <tr>
                        <th>Action</th>
                        <td id="detail-action"></td>
                    </tr>
                    <tr>
                        <th>Module</th>
                        <td id="detail-module"></td>
                    </tr>
                    <tr>
                        <th>Record ID</th>
                        <td id="detail-record"></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td id="detail-description"></td>
                    </tr>
                    <tr>
                        <th>IP Address</th>
                        <td id="detail-ip"></td>
                    </tr>
                </table>

                <!-- Old / New values -->
                <div id="changes-section" class="mt-3" style="display: none;">
                    <h6 class="font-weight-bold mb-2">Changes</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted font-weight-bold text-uppercase">Previous</small>
                            <pre id="detail-old" class="bg-light border p-2 mt-1" style="max-height: 260px; overflow-y: auto; font-size: 0.82rem;"></pre>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted font-weight-bold text-uppercase">Updated</small>
                            <pre id="detail-new" class="bg-light border p-2 mt-1" style="max-height: 260px; overflow-y: auto; font-size: 0.82rem;"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
    <script>
        const API_ROUTES = {
            getStudentLogs: "{{ route('teacher.audit.student_logs.data') }}",
            getLogDetail:   "{{ route('teacher.audit.student_logs.detail', ['id' => '__ID__']) }}"
        };
    </script>
    <script src="{{ asset('js/teacher/student_my_log.js') }}"></script>
@endsection