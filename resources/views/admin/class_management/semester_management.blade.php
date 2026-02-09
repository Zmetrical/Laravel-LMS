@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .semester-status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            font-weight: 500;
        }
        
        .data-table {
            font-size: 0.9rem;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .data-table tbody tr {
            transition: background-color 0.15s;
        }
        
        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .section-row {
            cursor: pointer;
        }
        
        .section-row.expanded {
            background-color: #e7f3ff;
        }
        
        .student-details-row {
            background-color: #f8f9fa;
        }
        
        .collapse-icon {
            transition: transform 0.2s;
        }
        
        .section-row.expanded .collapse-icon {
            transform: rotate(90deg);
        }
        
        .quick-actions {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem;
            margin: -1rem -1rem 1rem -1rem;
        }
        
        .enrollment-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
        }
        
        .student-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        .section-select-card {
            border: 2px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .section-select-card:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .section-select-card.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }
        
        .capacity-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .capacity-fill {
            height: 100%;
            background: #007bff;
            transition: width 0.3s;
        }
        
        .capacity-fill.warning {
            background: #ffc107;
        }
        
        .capacity-fill.danger {
            background: #dc3545;
        }
        
        .filter-pills {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-pill {
            padding: 0.25rem 0.75rem;
            background: #e9ecef;
            border-radius: 1rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-pill:hover {
            background: #007bff;
            color: white;
        }
        
        .filter-pill.active {
            background: #007bff;
            color: white;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            border-bottom: 3px solid #dee2e6;
            color: #6c757d;
        }
        
        .step.active {
            border-bottom-color: #007bff;
            color: #007bff;
            font-weight: 600;
        }
        
        .step.completed {
            border-bottom-color: #007bff;
            color: #007bff;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.schoolyears.index') }}">School Years</a></li>
        <li class="breadcrumb-item active">Semester Management</li>
    </ol>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Quick Enrollment Modal -->
    <div class="modal fade" id="quickEnrollModal" data-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i>Student Enrollment</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Step Indicator -->
                    <div class="step-indicator mb-4">
                        <div class="step active" data-step="1">
                            <i class="fas fa-users"></i>
                            <div class="small">Students</div>
                        </div>
                        <div class="step" data-step="2">
                            <i class="fas fa-bullseye"></i>
                            <div class="small">Target</div>
                        </div>
                        <div class="step" data-step="3">
                            <i class="fas fa-check"></i>
                            <div class="small">Confirm</div>
                        </div>
                    </div>

                    <!-- Step 1: Section & Student Selection -->
                    <div class="enrollment-step" id="step1">
                        <h6 class="mb-3"><i class="fas fa-users"></i> Select Students</h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Source Semester</label>
                                    <div class="form-control bg-light" id="sourceSemesterDisplay" style="cursor: not-allowed;">
                                        <span class="text-muted">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Target Semester</label>
                                    <div class="form-control bg-light" id="targetSemesterDisplay" style="cursor: not-allowed;">
                                        <span class="text-muted">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Select Section <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="qe_source_section" style="width: 100%;">
                                        <option value="">Type to search section...</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="studentSelectionArea" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><i class="fas fa-list"></i> Students in Section</h6>
                                <div class="filter-pills">
                                    <span class="filter-pill active" data-filter="all">All</span>
                                    <span class="filter-pill" data-filter="male">Male</span>
                                    <span class="filter-pill" data-filter="female">Female</span>
                                </div>
                            </div>
                            
                            <div class="quick-actions">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button type="button" class="btn btn-sm btn-default" id="qe_selectAll">
                                            <i class="fas fa-check-square"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-default" id="qe_deselectAll">
                                            <i class="fas fa-square"></i> Deselect All
                                        </button>
                                        <span class="ml-3 badge badge-primary" id="qe_studentCount">0 selected</span>
                                    </div>
                                    <div>
                                        <input type="text" class="form-control form-control-sm" id="studentSearch" 
                                               placeholder="Search student..." style="width: 250px;">
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover data-table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="40">
                                                <input type="checkbox" id="selectAllCheckbox" class="student-checkbox">
                                            </th>
                                            <th>Student Number</th>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th>Type</th>
                                            <th width="150">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="qe_studentList">
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="fas fa-arrow-up text-muted fa-2x mb-2"></i>
                                                <p class="text-muted">Select a section to load students</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-right mt-3">
                                <button type="button" class="btn btn-primary" id="btnStep1Next" disabled>
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Target Selection -->
                    <div class="enrollment-step" id="step2" style="display: none;">
                        <h6 class="mb-3"><i class="fas fa-bullseye"></i> Select Target Section</h6>
                        
                        <div id="qe_targetSections">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2">Loading available sections...</p>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-default" id="btnStep2Back">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary" id="btnStep2Next" disabled>
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Confirmation -->
                    <div class="enrollment-step" id="step3" style="display: none;">
                        <h6 class="mb-3"><i class="fas fa-check"></i> Review and Confirm</h6>
                        
                        <div class="enrollment-preview">
                            <div class="row">
                                <div class="col-md-4">
                                    <h6 class="text-muted">Students</h6>
                                    <h4 id="confirm_studentCount">-</h4>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-muted">Target Section</h6>
                                    <h4 id="confirm_targetSection">-</h4>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-muted">Capacity After</h6>
                                    <h4 id="confirm_capacity">-</h4>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div id="confirm_studentList" style="max-height: 300px; overflow-y: auto;">
                                <!-- Student list will be populated here -->
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-default" id="btnStep3Back">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary" id="btnEnrollConfirm">
                                <i class="fas fa-check"></i> Enroll Students
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Access Verification -->
    <div class="card card-primary card-outline" id="verificationCard">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-lock"></i> Verify Access</h3>
        </div>
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h5>Semester Management</h5>
                        <p class="text-muted">Enter your admin password to continue</p>
                    </div>
                    <form id="verificationForm">
                        <div class="form-group">
                            <label>Admin Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="adminPassword" 
                                   name="admin_password" autocomplete="off" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-check"></i> Verify Access
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="archiveContent" style="display: none;">
        <div id="contentLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
            <p class="mt-3">Loading semester information...</p>
        </div>

        <div id="mainContent" style="display: none;">
            <!-- School Year Header -->
            <div class="card card-dark mb-3">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt"></i>
                                <span id="syDisplay">-</span>
                            </h5>
                        </div>
                        <div class="col-md-6 text-right">
                            <span class="semester-status-badge mr-2" id="syStatusBadge"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Semesters Tabs -->
            <div class="card">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs" id="semesterTabs" role="tablist">
                        <!-- Tabs will be populated here -->
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="semesterTabContent">
                        <!-- Tab content will be populated here -->
                    </div>
                </div>
            </div>

            <div id="noSemesters" class="text-center py-5" style="display: none;">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">No semesters found</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script>
    const API_ROUTES = {
        verifyAccess: "{{ route('admin.archive.verify') }}",
        getArchiveInfo: "{{ route('admin.archive.info', ['id' => ':id']) }}",
        getSemesterDetails: "{{ route('admin.archive.semester-details', ['id' => ':id']) }}",
        getSectionStudents: "{{ route('admin.archive.section-students', ['semesterId' => ':semesterId', 'sectionId' => ':sectionId']) }}",
        completeSemester: "{{ route('admin.archive.complete-semester', ['id' => ':id']) }}",
        activateSemester: "{{ route('admin.semesters.set-active', ['id' => ':id']) }}",
        getPreviousSemester: "{{ route('admin.archive.get-previous-semester', ['id' => ':id']) }}", // NEW
        searchSections: "{{ route('admin.archive.search-sections') }}",
        loadStudents: "{{ route('admin.archive.load-students') }}",
        getSectionDetails: "{{ route('admin.archive.get-section-details') }}",
        getTargetSections: "{{ route('admin.archive.get-target-sections') }}",
        enrollStudents: "{{ route('admin.archive.enroll-students') }}",
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