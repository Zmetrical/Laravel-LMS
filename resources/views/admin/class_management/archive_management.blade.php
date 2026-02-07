@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
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
        <div class="row">
            <!-- School Years Archive -->
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt"></i> School Years
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Loading -->
                        <div id="syLoading" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Loading...</p>
                        </div>

                        <!-- Table -->
                        <div id="syTableContainer" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>School Year</th>
                                            <th>Status</th>
                                            <th width="100">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="syTableBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div id="syEmpty" class="text-center py-4" style="display: none;">
                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No school years available for archiving</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Semesters Archive -->
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i> Semesters
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Loading -->
                        <div id="semLoading" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Loading...</p>
                        </div>

                        <!-- Table -->
                        <div id="semTableContainer" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Semester</th>
                                            <th>School Year</th>
                                            <th>Status</th>
                                            <th width="100">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="semTableBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div id="semEmpty" class="text-center py-4" style="display: none;">
                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No semesters available for archiving</p>
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
            verifyAccess: "{{ route('admin.archive.verify') }}",
            getSchoolYears: "{{ route('admin.schoolyears.list') }}",
            getSemesters: "{{ route('admin.semesters.list') }}",
            archiveSchoolYear: "{{ route('admin.archive.school-year', ['id' => ':id']) }}",
            archiveSemester: "{{ route('admin.archive.semester', ['id' => ':id']) }}",
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection