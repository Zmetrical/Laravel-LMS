@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">School Year & Semester Management</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <div class="row">
        <!-- Left Panel: School Years List -->
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt"></i> School Years
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Status Filter -->
                    <div class="form-group">
                        <select class="form-control" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading school years...</p>
                    </div>

                    <!-- School Years List -->
                    <div id="schoolYearsContainer" style="display: none; max-height: 500px; overflow-y: auto;">
                        <div class="list-group" id="schoolYearsList">
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div id="emptyYears" class="text-center py-4" style="display: none;">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No school years found</p>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary btn-block" id="addSchoolYearBtn">
                        <i class="fas fa-plus"></i> Create School Year
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Panel: Details & Semesters -->
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title" id="detailsTitle">
                        <i class="fas fa-info-circle"></i> School Year Details
                    </h3>
                    <div class="card-tools" id="detailsTools" style="display: none;">
                        <button class="btn btn-tool text-white" id="editYearBtn" title="Edit School Year">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Empty State -->
                    <div id="emptyState" class="text-center py-5">
                        <i class="fas fa-arrow-left fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Please select a school year to view details</p>
                    </div>

                    <!-- Details Content -->
                    <div id="detailsContent" style="display: none;">
                        <!-- Action Buttons -->
                        <div class="mb-4" id="actionButtons">
                            <button class="btn btn-success btn-sm" id="activateBtn" style="display: none;">
                                <i class="fas fa-check-circle"></i> Set as Active
                            </button>
                            <button class="btn btn-warning btn-sm" id="archiveBtn" style="display: none;">
                                <i class="fas fa-archive"></i> Archive
                            </button>
                        </div>

                        <hr>

                        <!-- Semesters Section -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> Semesters
                            </h5>
                            <div>
                                <button class="btn btn-secondary btn-sm mr-2" id="viewSemestersBtn">
                                    <i class="fas fa-eye"></i> View Semesters
                                </button>
                                <button class="btn btn-primary btn-sm" id="addSemesterBtn">
                                    <i class="fas fa-plus"></i> Add Semester
                                </button>
                            </div>
                        </div>

                        <!-- Semesters Loading -->
                        <div id="semestersLoading" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin text-primary"></i>
                            <p class="mt-2">Loading semesters...</p>
                        </div>

                        <!-- Semesters Table -->
                        <div id="semestersTableContainer" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Semester</th>
                                            <th>Period</th>
                                            <th>Status</th>
                                            <th width="150">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="semestersTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- No Semesters -->
                        <div id="noSemesters" class="text-center py-4" style="display: none;">
                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No semesters added yet</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- School Year Modal -->
<div class="modal fade" id="schoolYearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-alt"></i> <span id="syModalTitle">Add School Year</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="schoolYearForm">
                <input type="hidden" id="schoolYearId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="yearStart">Start Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="yearStart" 
                               min="2000" max="2100" placeholder="e.g., 2024" required>
                    </div>
                    <div class="form-group">
                        <label for="yearEnd">End Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="yearEnd" 
                               min="2000" max="2100" placeholder="e.g., 2025" required>
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

<!-- Semester Modal -->
<div class="modal fade" id="semesterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-list"></i> <span id="semModalTitle">Add Semester</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="semesterForm">
                <input type="hidden" id="semesterId">
                <input type="hidden" id="semesterSchoolYearId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="semesterName">Semester Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="semesterName" 
                               placeholder="e.g., First Semester" required>
                    </div>
                    <div class="form-group">
                        <label for="semesterCode">Semester Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="semesterCode" 
                               placeholder="e.g., 1ST-SEM" required>
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
                    <div class="form-group" id="semStatusGroup" style="display: none;">
                        <label for="semStatus">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="semStatus">
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
            getSchoolYears: "{{ route('admin.schoolyears.list') }}",
            createSchoolYear: "{{ route('admin.schoolyears.create') }}",
            updateSchoolYear: "{{ route('admin.schoolyears.update', ['id' => ':id']) }}",
            setActive: "{{ route('admin.schoolyears.set-active', ['id' => ':id']) }}",
            getSemesters: "{{ route('admin.semesters.list') }}",
            createSemester: "{{ route('admin.semesters.create') }}",
            updateSemester: "{{ route('admin.semesters.update', ['id' => ':id']) }}",
            setActiveSemester: "{{ route('admin.semesters.set-active', ['id' => ':id']) }}",
            semestersPage: "{{ route('admin.semesters.index') }}",
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection