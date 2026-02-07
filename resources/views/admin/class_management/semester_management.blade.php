@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .info-card {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
        }
        .semester-card {
            transition: all 0.2s;
        }
        .semester-card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        
        .section-item-archive {
            padding: 0.75rem;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        .section-item-archive:hover {
            background: #f8f9fa;
        }
        .section-item-archive.expanded {
            background: #e7f3ff;
            border-color: #007bff;
        }
        
        .teacher-item {
            padding: 0.5rem 0.75rem;
            background: white;
            border-left: 3px solid #007bff;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
        }
        
        .class-tag {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #e7f3ff;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }
        
        .student-list {
            max-height: 300px;
            overflow-y: auto;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }
        .student-item {
            padding: 0.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .student-item:last-child {
            border-bottom: none;
        }
        
        .tab-content-area {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.25rem;
            margin-top: 0.5rem;
        }

        .view-students-link {
            text-decoration: none;
        }
        .view-students-link:hover {
            text-decoration: underline;
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

            <!-- Warning Box (if applicable) -->
            <div class="alert alert-warning" id="warningBox" style="display: none;">
                <h5><i class="fas fa-exclamation-triangle"></i> Important</h5>
                <p class="mb-0" id="warningText"></p>
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
            getSemesterDetails: "{{ route('admin.archive.semester-details', ['id' => ':id']) }}",
            getSectionStudents: "{{ route('admin.archive.section-students', ['semesterId' => ':semesterId', 'sectionId' => ':sectionId']) }}",
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