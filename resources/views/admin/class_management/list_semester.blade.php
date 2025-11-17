@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Semester Management</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <!-- School Year Info Header -->
        <div class="alert alert-dark alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <div id="schoolYearHeader">
                <i class="fas fa-spinner fa-spin"></i> Loading school year information...
            </div>
        </div>

        <div class="row">
            <!-- School Years List (Left Panel) -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar"></i> Select School Year</h3>
                    </div>
                    <div class="card-body">
                        <div id="schoolYearsListContainer" style="max-height: 600px; overflow-y: auto;">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Loading school years...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Semesters List (Right Panel) -->
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i> Semesters
                        </h3>
                        <div class="card-tools">
                            <button class="btn btn-primary btn-sm" id="addSemesterBtn" disabled>
                                <i class="fas fa-plus"></i> Add Semester
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="noSchoolYearSelected" class="text-center py-5">
                            <i class="fas fa-arrow-left fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Please select a school year to view semesters</p>
                        </div>

                        <div id="semestersContainer" style="display: none;">
                            <div id="selectedSchoolYearInfo" class="alert alert-light">
                            </div>

                            <div id="semestersLoadingIndicator" class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Loading semesters...</p>
                            </div>

                            <div id="semestersTableContainer" style="display: none;">
                                <table class="table table-bordered table-hover" id="semestersTable">
                                    <thead class="bg-primary">
                                        <tr>
                                            <th width="50">#</th>
                                            <th>Semester</th>
                                            <th>Code</th>
                                            <th>Duration</th>
                                            <th width="100">Status</th>
                                            <th width="150">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="semestersTableBody">
                                    </tbody>
                                </table>
                            </div>

                            <div id="noSemestersMessage" class="text-center py-4" style="display: none;">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No semesters found for this school year</p>
                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const API_ROUTES = {
            getSchoolYears: "{{ route('admin.schoolyears.list') }}",
            getSemesters: "{{ route('admin.semesters.list') }}",
            createSemester: "{{ route('admin.semesters.create') }}",
            updateSemester: "{{ route('admin.semesters.update', ['id' => ':id']) }}",
            setActive: "{{ route('admin.semesters.set-active', ['id' => ':id']) }}"
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection