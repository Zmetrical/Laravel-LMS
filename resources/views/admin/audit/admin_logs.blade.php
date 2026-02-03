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
        .json-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
        }
        .badge-action {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Admin Audit Logs</li>
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
                    <label>Search</label>
                    <input type="text" class="form-control" id="searchLog" 
                           placeholder="User, action, module...">
                </div>
                <div class="col-md-2">
                    <label>Action</label>
                    <select class="form-control" id="actionFilter">
                        <option value="">All Actions</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                        <option value="viewed">Viewed</option>
                        <option value="exported">Exported</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Module</label>
                    <select class="form-control" id="moduleFilter">
                        <option value="">All Modules</option>
                        <option value="students">Students</option>
                        <option value="teachers">Teachers</option>
                        <option value="classes">Classes</option>
                        <option value="grades">Grades</option>
                        <option value="sections">Sections</option>
                        <option value="strands">Strands</option>
                        <option value="enrollment">Enrollment</option>
                        <option value="system">System</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Date Range</label>
                    <input type="date" class="form-control" id="dateFrom">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
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
            <h3 class="card-title"><i class="fas fa-history mr-2"></i>Admin Activity Logs</h3>
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
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Record ID</th>
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
                        <th>Admin</th>
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
                
                <div id="changes-section" style="display: none;">
                    <h6 class="mt-3">Changes:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Old Values:</strong>
                            <pre id="detail-old" class="bg-light p-2" style="max-height: 300px; overflow-y: auto;"></pre>
                        </div>
                        <div class="col-md-6">
                            <strong>New Values:</strong>
                            <pre id="detail-new" class="bg-light p-2" style="max-height: 300px; overflow-y: auto;"></pre>
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
            getLogs: "{{ route('admin.audit.logs.data') }}"
        };
    </script>
    <script src="{{ asset('js/audit/admin_logs.js') }}"></script>
@endsection