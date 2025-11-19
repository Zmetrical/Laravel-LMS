@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
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
    <div class="card card-dark card-outline mb-3">
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
                    <small class="text-muted">Code: <span id="codeDisplay">-</span></small>
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
                    <div class="card-tools">
                        <button class="btn btn-primary btn-xs" id="addSemesterBtn" disabled>
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
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
                        <p class="text-muted mb-2">No semesters found</p>
                        <button class="btn btn-primary btn-sm" id="addFirstSemesterBtn">
                            <i class="fas fa-plus"></i> Add Semester
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enrolled Classes -->
            <div class="card card-dark card-outline" id="classesCard" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-book"></i> Enrolled Classes
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div id="classesLoading" class="text-center py-3">
                        <i class="fas fa-spinner fa-spin"></i> Loading classes...
                    </div>

                    <div id="classesTable" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Class Code</th>
                                        <th>Class Name</th>
                                        <th width="60" class="text-center">Students</th>
                                    </tr>
                                </thead>
                                <tbody id="classesTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="noClasses" class="text-center py-4" style="display: none;">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No classes enrolled</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Student Enrollment List -->
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title" id="detailsTitle">
                        <i class="fas fa-users"></i> Enrolled Students
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-primary mr-2" id="studentCount">0 Students</span>
                    </div>
                </div>
                <div class="card-body" style="min-height: 500px;">
                    <!-- Empty State -->
                    <div id="emptyState" class="text-center text-muted py-5">
                        <i class="fas fa-arrow-left fa-3x mb-3"></i>
                        <h5>Select a Class</h5>
                        <p>Choose a class from the list to view enrolled students</p>
                    </div>

                    <!-- Students Loading -->
                    <div id="studentsLoading" class="text-center py-5" style="display: none;">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading students...</p>
                    </div>

                    <!-- Students Table -->
                    <div id="studentsContent" style="display: none;">
                        <!-- Class Info Bar -->
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                            <div>
                                <h6 class="mb-1"><span id="selectedClassName">-</span></h6>
                                <small class="text-muted">
                                    <span id="selectedClassCode">-</span> | 
                                    <span id="selectedSemesterName">-</span>
                                </small>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-dark">
                                    <tr>
                                        <th width="120">Student Number</th>
                                        <th>Name</th>
                                        <th width="150">Section</th>
                                        <th width="100">Status</th>
                                        <th width="90" class="text-center">Final Grade</th>
                                        <th width="80">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="studentsTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- No Students State -->
                    <div id="noStudents" class="text-center py-5" style="display: none;">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No students enrolled in this class</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Semester Modal -->
<div class="modal fade" id="semesterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus"></i> <span id="modalTitle">Add Semester</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="semesterForm">
                <input type="hidden" id="semesterId">
                <input type="hidden" id="schoolYearId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="semesterName">Semester Name <span class="text-danger">*</span></label>
                                <select class="form-control" id="semesterName" required>
                                    <option value="">Select Semester</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="semesterCode">Semester Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="semesterCode" 
                                       placeholder="e.g., SEM1, SEM2, SUMMER" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="startDate">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="startDate" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endDate">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="endDate" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="statusGroup" style="display: none;">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status">
                            <option value="upcoming">Upcoming</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            getSchoolYear: "{{ route('admin.schoolyears.list') }}",
            getSemesters: "{{ route('admin.semesters.list') }}",
            createSemester: "{{ route('admin.semesters.create') }}",
            updateSemester: "{{ route('admin.semesters.update', ['id' => ':id']) }}",
            setActive: "{{ route('admin.semesters.set-active', ['id' => ':id']) }}",
            getSemesterClasses: "{{ route('admin.semesters.classes', ['id' => ':id']) }}",
            getEnrollmentHistory: "{{ route('admin.semesters.enrollment-history', ['semesterId' => ':semesterId', 'classCode' => ':classCode']) }}",
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