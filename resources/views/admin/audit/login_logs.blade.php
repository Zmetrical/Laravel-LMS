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
        <li class="breadcrumb-item active">Login History</li>
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
                        <option value="">All Types</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                        <option value="guardian">Guardian</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Search User</label>
                    <input type="text" class="form-control" id="searchUser" 
                           placeholder="Email, student number...">
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select class="form-control" id="statusFilter">
                        <option value="">All Sessions</option>
                        <option value="active">Active</option>
                        <option value="logout">Logged Out</option>
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

    <!-- Login History Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-sign-in-alt mr-2"></i>Login History</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="logsCount">0 Sessions</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="loginTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Login Time</th>
                            <th>User Type</th>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>Session Duration</th>
                            <th>Logout Time</th>
                            <th>Status</th>
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
                <h5 class="modal-title">Login Session Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="150">User Type</th>
                        <td id="detail-usertype"></td>
                    </tr>
                    <tr>
                        <th>User</th>
                        <td id="detail-user"></td>
                    </tr>
                    <tr>
                        <th>Login Time</th>
                        <td id="detail-login"></td>
                    </tr>
                    <tr>
                        <th>Logout Time</th>
                        <td id="detail-logout"></td>
                    </tr>
                    <tr>
                        <th>Session Duration</th>
                        <td id="detail-duration"></td>
                    </tr>
                    <tr>
                        <th>IP Address</th>
                        <td id="detail-ip"></td>
                    </tr>
                    <tr>
                        <th>Session ID</th>
                        <td id="detail-session"></td>
                    </tr>
                    <tr>
                        <th>User Agent</th>
                        <td id="detail-agent" style="word-break: break-all;"></td>
                    </tr>
                    <tr>
                        <th>Browser</th>
                        <td id="detail-browser"></td>
                    </tr>
                    <tr>
                        <th>Platform</th>
                        <td id="detail-platform"></td>
                    </tr>
                </table>
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
            getLogs: "{{ route('admin.audit.login.data') }}",
            getLogDetails: "{{ route('admin.audit.login.details', ':id') }}"
        };
    </script>
    <script src="{{ asset('js/audit/login_logs.js') }}"></script>
@endsection