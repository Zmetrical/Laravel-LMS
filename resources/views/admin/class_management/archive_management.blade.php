@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .info-card {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
        }
        .stat-box {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 0.25rem;
            border: 1px solid #dee2e6;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .semester-card {
            transition: all 0.2s;
        }
        .semester-card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.schoolyears.index') }}">School Years</a></li>
        <li class="breadcrumb-item active">Archive Management</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Access Verification Card -->
    <div class="card card-primary card-outline" id="verificationCard">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-lock"></i> Verify Access
            </h3>
        </div>
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h5>Archive Management</h5>
                        <p class="text-muted">Please verify your admin password to access archive operations</p>
                    </div>
                    <form id="verificationForm">
                        <div class="form-group">
                            <label for="adminPassword">Admin Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control" 
                                   id="adminPassword" 
                                   name="admin_password" 
                                   placeholder="Enter your admin password"
                                   autocomplete="off"
                                   required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-check"></i> Verify Access
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Archive Management Content (Hidden until verified) -->
    <div id="archiveContent" style="display: none;">
        <!-- Loading State -->
        <div id="contentLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
            <p class="mt-3">Loading archive information...</p>
        </div>

        <!-- Main Content -->
        <div id="mainContent" style="display: none;">
            <!-- School Year Info -->
            <div class="card card-dark mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt"></i>
                                <span id="syDisplay">-</span>
                            </h5>
                        </div>
                        <div>
                            <span class="badge badge-lg mr-2" id="syStatusBadge"></span>
                            <button class="btn btn-sm btn-secondary" id="archiveSYBtn" style="display: none;">
                                <i class="fas fa-archive"></i> Archive School Year
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Semesters -->
            <div class="row" id="semestersContainer">
                <!-- Semesters will be populated here -->
            </div>

            <!-- Empty State -->
            <div id="noSemesters" class="text-center py-5" style="display: none;">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">No semesters found for this school year</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            verifyAccess: "{{ route('admin.archive.verify') }}",
            getArchiveInfo: "{{ route('admin.archive.info', ['id' => ':id']) }}",
            archiveSchoolYear: "{{ route('admin.archive.school-year', ['id' => ':id']) }}",
            archiveSemester: "{{ route('admin.archive.semester', ['id' => ':id']) }}",
            activateSemester: "{{ route('admin.semesters.set-active', ['id' => ':id']) }}",
            csrfToken: "{{ csrf_token() }}"
        };

        const SCHOOL_YEAR_ID = {{ $school_year_id }};
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection