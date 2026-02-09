@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .quarter-tabs .nav-link {
            border-radius: 0;
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            padding: 0.75rem 1.5rem;
        }
        .quarter-tabs .nav-link:hover {
            background-color: #f8f9fa;
            color: #495057;
        }
        .quarter-tabs .nav-link.active {
            color: #007bff;
            border-bottom-color: #007bff;
            background-color: #fff;
            font-weight: 600;
        }
        .semester-item.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .semester-item.active .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        .semester-item.active .badge {
            background-color: rgba(255, 255, 255, 0.2) !important;
            color: white;
        }
        .section-item {
            cursor: pointer;
            transition: all 0.2s;
        }
        .section-item:hover {
            background-color: #f8f9fa;
        }
        .section-item.active {
            background-color: #007bff;
            color: white !important;
        }
        .section-item.active .text-muted,
        .section-item.active small {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        .section-item.active .badge {
            background-color: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }

        /* Expandable Row Styles */
        .expand-btn {
            cursor: pointer;
            transition: transform 0.2s;
            border: none;
            background: transparent;
            padding: 0.25rem 0.5rem;
            color: #6c757d;
        }
        .expand-btn:hover {
            color: #007bff;
        }
        .expand-btn.expanded {
            transform: rotate(90deg);
        }
        .expand-btn i {
            font-size: 1rem;
        }
        
        .classes-detail-row {
            background-color: #f8f9fa;
        }
        .classes-detail-cell {
            padding: 1rem !important;
            border-top: none !important;
        }
        .class-item {
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border-left: 3px solid #007bff;
            border-radius: 0.25rem;
        }
        .class-item:last-child {
            margin-bottom: 0;
        }
        .class-name {
            color: #495057;
            font-weight: 500;
        }
        .grade-badges {
            margin-top: 0.25rem;
        }
        .grade-badges .badge {
            margin-right: 0.25rem;
            font-size: 0.75rem;
        }
        .no-classes {
            padding: 1rem;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }

        /* Bootstrap searchable dropdown */
        .dropdown .dropdown-menu {
            min-width: 100%;
            border-radius: 0.25rem;
        }
        .dropdown .form-control {
            cursor: text;
        }
        .class-option[hidden] {
            display: none !important;
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
<br>
<div class="container-fluid">
    <!-- School Year Info Header -->
    <div class="card card-dark mb-3">
        <div class="card-body p-3">
            <div id="schoolYearLoading" class="text-center py-2">
                <i class="fas fa-spinner fa-spin"></i> Loading school year...
            </div>
            <div id="schoolYearInfo" class="d-flex justify-content-between align-items-center" style="display: none !important;">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="syDisplay">-</span>
                    </h5>
                </div>
                <span class="badge badge-lg" id="statusBadge"></span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Panel -->
        <div class="col-md-4">
            <!-- Semesters List -->
            <div class="card card-primary card-outline mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Semesters
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div id="semestersLoading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading...</p>
                    </div>

                    <div id="semestersList" class="list-group list-group-flush" style="display: none;">
                    </div>

                    <div id="noSemesters" class="text-center py-4" style="display: none;">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No semesters found</p>
                    </div>
                </div>
            </div>

            <!-- Enrolled Sections -->
            <div class="card card-primary card-outline" id="sectionsCard" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Sections
                    </h3>
                </div>
                <div class="card-body p-0">
                    <!-- Section Filters -->
                    <div id="sectionFiltersContainer" style="display: none;">
                        <div class="p-3">
                            <input type="text" class="form-control form-control-sm mb-2" id="sectionSearch" 
                                   placeholder="Search sections...">
                        </div>
                        <div class="px-3 pb-3">
                            <select class="form-control form-control-sm mb-2" id="sectionLevelFilter">
                                <option value="">All Levels</option>
                            </select>
                            <select class="form-control form-control-sm" id="sectionStrandFilter">
                                <option value="">All Strands</option>
                            </select>
                        </div>
                    </div>

                    <div id="sectionsLoading" class="text-center py-3">
                        <i class="fas fa-spinner fa-spin"></i> Loading sections...
                    </div>

                    <div id="sectionsList" style="display: none;">
                        <div class="list-group list-group-flush" id="sectionsListContainer" 
                             style="max-height: 550px; overflow-y: auto;">
                        </div>
                    </div>

                    <div id="noSections" class="text-center py-4" style="display: none;">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No sections enrolled</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Student Enrollment List -->
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title" id="detailsTitle">
                        <i class="fas fa-graduation-cap"></i> Student List
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-primary mr-2" id="studentCount">0 Students</span>
                    </div>
                </div>

                <!-- Quarter Tabs -->
                <div class="card-header border-0 pb-0 pt-0" id="quarterTabsSection" style="display: none;">
                    <ul class="nav nav-tabs quarter-tabs card-header-tabs mb-0">
                        <!-- Tabs will be populated dynamically -->
                    </ul>
                </div>

                <!-- Filters Card -->
                <div class="card-body pb-0" id="filtersSection" style="display: none;">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" class="form-control" id="studentSearch"
                                    placeholder="Search by student name or number...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="dropdown w-100">
                                <input 
                                    type="text" 
                                    id="classSearchInput" 
                                    class="form-control form-control-sm" 
                                    placeholder="All Classes (Overview)" 
                                    data-toggle="dropdown"
                                    autocomplete="off"
                                    aria-haspopup="true" 
                                    aria-expanded="false"
                                    readonly
                                >
                                <ul class="dropdown-menu w-100" id="classDropdownList" style="max-height: 280px; overflow-y: auto;">
                                    <li><a class="dropdown-item class-option" data-id="">All Classes (Overview)</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control form-control-sm" id="remarksFilter">
                                <option value="">All Remarks</option>
                                <option value="PASSED">Passed</option>
                                <option value="FAILED">Failed</option>
                                <option value="INC">Incomplete</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-default btn-sm btn-block" id="resetFiltersBtn" title="Reset Filters">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>
                    <hr class="mt-3 mb-0">
                </div>

                <div class="card-body m-0 p-0" style="min-height: 500px;">
                    <!-- Empty State -->
                    <div id="emptyState" class="text-center text-muted py-5">
                        <i class="fas fa-arrow-left fa-3x mb-3"></i>
                        <h5>Select a Section</h5>
                        <p>Choose a section from the list to view enrolled students</p>
                    </div>

                    <!-- Students Loading -->
                    <div id="studentsLoading" class="text-center py-5" style="display: none;">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading students...</p>
                    </div>

                    <!-- Students Table -->
                    <div id="studentsContent" class="p-0" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="studentsTable">
                                <thead class="bg-light" id="studentsTableHead">
                                </thead>
                                <tbody id="studentsTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- No Students State -->
                    <div id="noStudents" class="text-center py-5" style="display: none;">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No students enrolled in this section</p>
                    </div>
                </div>
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
        getSchoolYear: "{{ route('admin.schoolyears.list') }}",
        getSemesters: "{{ route('admin.semesters.list') }}",
        getSemesterSections: "{{ route('admin.semesters.sections', ['id' => ':id']) }}",
        getSectionEnrollment: "{{ route('admin.sections.enrollment', ['semesterId' => ':semesterId', 'sectionId' => ':sectionId']) }}",
        viewGradeCard: "{{ route('admin.grades.card.view.page', ['student_number' => ':student_number', 'semester_id' => ':semester_id']) }}",
        csrfToken: "{{ csrf_token() }}"
    };

    const urlParams = new URLSearchParams(window.location.search);
    const SCHOOL_YEAR_ID = urlParams.get('sy');
</script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection