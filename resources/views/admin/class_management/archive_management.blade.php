@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        .semester-item {
            cursor: pointer;
            transition: all 0.2s;
        }
        .semester-item:hover {
            background-color: #f8f9fa;
        }
        .semester-item.active {
            background-color: #007bff;
            color: white !important;
        }
        .semester-item.active .text-muted,
        .semester-item.active small {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        .semester-item.active .badge {
            background-color: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
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
    <!-- Verification Card -->
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
                        <p class="text-muted">Enter your admin password to continue</p>
                    </div>
                    <form id="verificationForm">
                        <div class="form-group">
                            <label>Admin Password</label>
                            <input type="password" class="form-control" id="adminPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-check"></i> Verify
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="archiveContent" style="display: none;">
        <!-- Loading -->
        <div id="contentLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
            <p class="mt-3">Loading...</p>
        </div>

        <!-- Content -->
        <div id="mainContent" style="display: none;">
            <!-- School Year Header -->
            <div class="card card-dark mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt"></i>
                            <span id="syDisplay">-</span>
                        </h5>
                        <div>
                            <span class="badge badge-lg mr-2" id="syStatusBadge"></span>
                            <button class="btn btn-sm btn-secondary" id="archiveSYBtn" style="display: none;">
                                <i class="fas fa-archive"></i> Archive School Year
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left: Semesters -->
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar"></i> Semesters
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div id="semestersLoading" class="text-center py-4">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                            <div id="semestersList" class="list-group list-group-flush" style="display: none;">
                            </div>
                            <div id="noSemesters" class="text-center py-4" style="display: none;">
                                <p class="text-muted mb-0">No semesters</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Details -->
                <div class="col-md-8">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Details
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- Empty State -->
                            <div id="emptyState" class="text-center py-5">
                                <i class="fas fa-arrow-left fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Select a semester</p>
                            </div>

                            <!-- Loading -->
                            <div id="detailsLoading" class="text-center py-5" style="display: none;">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                            </div>

                            <!-- Content -->
                            <div id="detailsContent" style="display: none;">
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="mb-0 text-primary" id="studentsCount">0</h4>
                                            <small class="text-muted">Students</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="mb-0 text-primary" id="sectionsCount">0</h4>
                                            <small class="text-muted">Sections</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="mb-0 text-primary" id="teachersCount">0</h4>
                                            <small class="text-muted">Teachers</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="mb-0 text-primary" id="gradesCount">0</h4>
                                            <small class="text-muted">Grades</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tables -->
                                <ul class="nav nav-tabs mb-3">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#sectionsTab">Sections</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#teachersTab">Teachers</a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Sections Tab -->
                                    <div class="tab-pane fade show active" id="sectionsTab">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Section</th>
                                                        <th>Level</th>
                                                        <th>Strand</th>
                                                        <th class="text-right">Students</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="sectionsTableBody">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Teachers Tab -->
                                    <div class="tab-pane fade" id="teachersTab">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Teacher</th>
                                                        <th>Classes</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="teachersTableBody">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <div class="text-right" id="actionButtons">
                                    <!-- Buttons will be added here -->
                                </div>
                            </div>
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
            getArchiveInfo: "{{ route('admin.archive.info', ['id' => ':id']) }}",
            getSemesterDetails: "{{ route('admin.archive.semester-details', ['id' => ':id']) }}",
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