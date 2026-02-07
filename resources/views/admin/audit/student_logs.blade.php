@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
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
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Student & Guardian Audit Logs</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label>User Type</label>
                    <select class="form-control" id="userTypeFilter">
                        <option value="">All</option>
                        <option value="student">Student</option>
                        <option value="guardian">Guardian</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Search User</label>
                    <input type="text" class="form-control" id="searchUser" 
                           placeholder="Student number, name, or email...">
                </div>
                <div class="col-md-2">
                    <label>Action</label>
                    <select class="form-control" id="actionFilter">
                        <option value="">All Actions</option>
                        <option value="viewed">Viewed</option>
                        <option value="submitted">Submitted</option>
                        <option value="completed">Completed</option>
                        <option value="accessed">Accessed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Module</label>
                    <select class="form-control" id="moduleFilter">
                        <option value="">All Modules</option>
                        <option value="quizzes">Quizzes</option>
                        <option value="lessons">Lessons</option>
                        <option value="grades">Grades</option>
                        <option value="lectures">Lectures</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label>Date From</label>
                    <input type="date" class="form-control" id="dateFrom">
                </div>
                <div class="col-md-1">
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

    <!-- Audit Logs Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-graduate mr-2"></i>Student & Guardian Activity Logs</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="logsCount">0 Logs</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="auditTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>User Type</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th class="text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via AJAX -->
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
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="150">Timestamp</th>
                        <td id="detail-timestamp"></td>
                    </tr>
                    <tr>
                        <th>User Type</th>
                        <td id="detail-usertype"></td>
                    </tr>
                    <tr>
                        <th>User</th>
                        <td id="detail-user"></td>
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
                    <tr>
                        <th>User Agent</th>
                        <td id="detail-agent"></td>
                    </tr>
                </table>
                
                <div id="values-section" style="display: none;">
                    <h6 class="mt-3">Additional Data:</h6>
                    <pre id="detail-values" class="bg-light p-2" style="max-height: 300px; overflow-y: auto;"></pre>
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
            getLogs: "{{ route('admin.audit.students.data') }}",
            getLogDetails: "{{ route('admin.audit.students.details', ':id') }}"
        };
    </script>
    <script src="{{ asset('js/audit/student_logs.js') }}"></script>
@endsection