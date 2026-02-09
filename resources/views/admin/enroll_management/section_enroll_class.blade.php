@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Section Enrollment</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <!-- Active Semester Info -->
        <div class="alert alert-dark">
            <h5><i class="icon fas fa-calendar-alt"></i> {{ $activeSemesterDisplay ?? 'No Active Semester' }}</h5>
        </div>
        
        <div class="row">
            <!-- Left Sidebar - Section List -->
            <div class="col-md-3">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users"></i> Sections</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-3 ">
                            <input type="text" class="form-control form-control-sm mb-2" id="sectionSearch" placeholder="Search sections...">
                            <input type="text" class="form-control form-control-sm" id="adviserFilter" placeholder="Filter by adviser...">
                        </div>
                        <div class="pb-3 px-3">
                            <select class="form-control form-control-sm mb-2" id="levelFilter">
                                <option value="">All Levels</option>
                            </select>
                            <select class="form-control form-control-sm mb-2" id="strandFilter">
                                <option value="">All Strands</option>
                            </select>
                        </div>


                        <div class="list-group list-group-flush" id="sectionsListContainer"
                            style="max-height: 550px; overflow-y: auto;">
                            <div class="text-center py-4">
                                 <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2 mb-0 small">Loading sections...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div id="noSectionSelected" class="card card-primary card-outline">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-arrow-left fa-3x text-muted mb-3"></i>
                        <h5>No Section Selected</h5>
                        <p class="text-muted">Please select a section from the left sidebar to manage enrollments.</p>
                    </div>
                </div>

                <div id="enrollmentSection" style="display:none;">
                    <!-- Section Info Header -->
                    <div class="card card-primary">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h4 class="mb-1" id="selectedSectionName"><i class="fas fa-users"></i> </h4>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-layer-group"></i> <span id="levelDisplay"></span> | 
                                        <i class="fas fa-graduation-cap"></i> <span id="strandDisplay"></span>
                                    </p>
                                    <div id="adviserDisplay">
                                        <small class="text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Loading adviser...
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button class="btn btn-primary mb-2" id="assignAdviserBtn">
                                        <i class="fas fa-user-tie"></i> Assign Adviser
                                    </button>
                                    <button class="btn btn-primary" id="enrollClassBtn">
                                        <i class="fas fa-plus"></i> Enroll Class
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrolled Classes -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-book"></i> Enrolled Classes</h3>
                            <div class="card-tools">
                                <span class="badge badge-light" id="classesCount">0 Classes</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-striped table-hover mb-0">
                                    <thead style="position: sticky; top: 0; z-index: 1; background: white;">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="35%">Class Name</th>
                                            <th width="35%">Teacher(s)</th>
                                            <th width="10%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="enrolledClassesBody">
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-primary">
                                                <i class="fas fa-spinner fa-spin"></i> Loading ...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Students in Section -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-graduate"></i> Students in Section</h3>
                            <div class="card-tools">
                                <span class="badge badge-light" id="studentsCount">0 Students</span>
                            </div>
                        </div>
                        
                        <!-- Filters inside card body -->
                        <div class="card-body pb-0">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="studentSearchFilter"
                                            placeholder="Search by name or student number...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-control form-control-sm" id="genderFilter">
                                        <option value="">All Genders</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control form-control-sm" id="studentTypeFilter">
                                        <option value="">All Types</option>
                                        <option value="regular">Regular</option>
                                        <option value="irregular">Irregular</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-secondary btn-sm btn-block" id="resetStudentFiltersBtn">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body p-0 pt-3">
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-striped table-hover mb-0">
                                    <thead style="position: sticky; top: 0; z-index: 1; background: white;">
                                        <tr>
                                            <th width="15%">Student Number</th>
                                            <th width="30%">Name</th>
                                            <th width="10%" class="text-center">Gender</th>
                                            <th width="25%">Email</th>
                                            <th width="15%" class="text-center">Type</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentsBody">
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-primary">
                                                <i class="fas fa-spinner fa-spin"></i> Loading ...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Enroll Class Modal -->
<div class="modal fade" id="enrollClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Enroll Classes
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Class Selection -->
<div class="form-group">
    <label><i class="fas fa-book"></i> Select Class to Add</label>
    <select class="form-control" id="availableClassesSelect" style="width: 100%;">
        <option value="">Search for class...</option>
    </select>
</div>

<div class="form-group">
    <button class="btn btn-primary btn-block" type="button" id="addClassToListBtn" disabled>
        <i class="fas fa-plus"></i> Add to List
    </button>
</div>

                <!-- Selected Classes List -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list"></i> Classes to Enroll 
                            <span class="badge badge-primary ml-2" id="selectedClassesCount">0</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div id="selectedClassesContainer" style="max-height: 300px; overflow-y: auto;">
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p class="mb-0">No classes selected yet</p>
                                <small>Select a class above and click "Add to List"</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmEnrollBtn" disabled>
                    <i class="fas fa-check"></i> Enroll <span id="enrollCountBadge">0</span> Class(es)
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Assign Adviser Modal -->
<div class="modal fade" id="assignAdviserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-user-tie"></i> Manage Section Adviser
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Teacher Selection -->
                <div class="form-group">
                    <label><i class="fas fa-user-check"></i> Assign New Adviser</label>
                    <select class="form-control select2" id="adviserTeacherSelect" style="width: 100%;">
                        <option value="">-- Select Teacher --</option>
                    </select>
                </div>

                <!-- Current Adviser Display -->
                <div id="currentAdviserSection" style="display:none;">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-user-circle"></i> Current Adviser
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1" id="currentAdviserNameModal"></h5>
                                    <p class="mb-0 small">
                                        <i class="fas fa-envelope"></i> <span id="currentAdviserEmail"></span>
                                    </p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button class="btn btn-danger btn-sm" id="removeAdviserBtn">
                                        <i class="fas fa-times"></i> Remove Adviser
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-secondary" id="confirmAssignAdviserBtn">
                    <i class="fas fa-check"></i> Assign Adviser
                </button>
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
            getSections: "{{ route('admin.sections.list') }}",
            getSectionDetails: "{{ route('admin.sections.details', ['id' => ':id']) }}",
            getAvailableClasses: "{{ route('admin.classes.available', ['sectionId' => ':id']) }}",
            enrollClass: "{{ route('admin.sections.enroll', ['id' => ':id']) }}",
            removeClass: "{{ route('admin.sections.remove-class', ['sectionId' => ':sectionId', 'classId' => ':classId']) }}",
             getSectionAdviser: "{{ route('admin.sections.adviser', ['id' => ':id']) }}",
            getAvailableTeachers: "{{ route('admin.teachers.available') }}",
            assignAdviser: "{{ route('admin.sections.assign-adviser', ['id' => ':id']) }}",
            removeAdviser: "{{ route('admin.sections.remove-adviser', ['id' => ':id']) }}"
        };

        const ACTIVE_SEMESTER_ID = {{ $activeSemester->semester_id ?? 'null' }};
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif

@endsection