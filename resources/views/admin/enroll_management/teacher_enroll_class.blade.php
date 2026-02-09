@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Classes</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <!-- School Year Info -->
        <div class="alert alert-dark">
            <h5><i class="icon fas fa-calendar-alt"></i> {{ $activeSemesterDisplay ?? 'No Active Semester' }}</h5>
        </div>
        <div class="row">
            <!-- Left Sidebar - Class List -->
            <div class="col-md-3">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book"></i> Classes</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-3">
                            <input type="text" class="form-control" id="classSearch" placeholder="Search classes...">
                        </div>
                        <div class="list-group list-group-flush" id="classListGroup"
                            style="max-height: 600px; overflow-y: auto;">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0">Loading classes...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content - Students -->
            <div class="col-md-9">
                <div id="noClassSelected" class="card card-primary card-outline">
                    <div class="card-body text-center py-5">
                        <h5>No Class Selected</h5>
                        <p class="text-muted">Please select a class from the left sidebar to view enrolled students.</p>
                    </div>
                </div>

                <div id="enrollmentSection" style="display:none;">
                    <!-- Class Info Header -->
                    <div class="card card-primary">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <h4 class="mb-1" id="selectedClassName"><i class="fas fa-book-open"></i> </h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher & Class Info -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between">
                                
                                <div class="p-2" style="flex: 1 1 30%;">
                                    <strong class="d-block mb-1" style="font-size: 1.1rem;">
                                        <i class="fas fa-chalkboard-teacher text-dark"></i> Assigned Teacher:
                                    </strong>
                                    <span id="teacherNameDisplay"
                                        class="badge badge-secondary p-2"
                                        style="font-size: 1rem;">
                                        None
                                    </span>
                                </div>

                                <!-- Enrolled Sections (Current Semester Only) -->
                                <div class="p-2" style="flex: 1 1 40%;" id="enrolledSectionsContainer">
                                    <div class="text-center text-muted py-2">
                                        <i class="fas fa-spinner fa-spin"></i> Loading sections...
                                    </div>
                                </div>

                                <!-- Button -->
                                <div class="p-2 text-right" style="flex: 1 1 25%;">
                                    <button class="btn btn-primary" id="assignTeacherBtn">
                                        <i class="fas fa-user-tie"></i> Manage Teacher
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <div class="card card-primary">
                        
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list"></i> Student List</h3>
                            <div class="card-tools">
                                <span class="badge badge-light" id="studentsCount">0 Students</span>
                            </div>
                        </div>

                        <div class="card-body pb-0">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="studentSearch"
                                            placeholder="Search by student name or number...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-control" id="sectionFilter">
                                        <option value="">All Sections</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control" id="studentTypeFilter">
                                        <option value="">All Types</option>
                                        <option value="regular">Regular</option>
                                        <option value="irregular">Irregular</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-default btn-block" id="resetFiltersBtn">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-striped table-hover mb-0">
                                    <thead style="position: sticky; top: 0; z-index: 1; background: white;">
                                        <tr>
                                            <th width="15%">Student No.</th>
                                            <th width="25%">Full Name</th>
                                            <th width="15%">Grade Level</th>
                                            <th width="15%">Strand</th>
                                            <th width="20%">Section</th>
                                            <th width="10%" class="text-center">Type</th>
                                        </tr>
                                    </thead>
                                    <tbody id="enrolledStudentsBody">
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="sr-only">Loading...</span>
                                                </div>
                                                <p class="mt-2 mb-0">Loading students...</p>
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

    <!-- Assign Teacher Modal -->
    <div class="modal fade" id="assignTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">
                        <i class="fas fa-user-tie"></i> Manage Teacher Assignment
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Teacher Selection -->
                    <div class="form-group">
                        <label><i class="fas fa-user-check"></i> Assign New Teacher</label>
                        <select class="form-control select2" id="teacherSelect" style="width: 100%;">
                            <option value="">-- Select Teacher --</option>
                        </select>
                    </div>

                    <!-- Current Teacher Display -->
                    <div id="currentTeacherSection" style="display:none;">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-user-circle"></i> Current Assignment
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-1" id="currentTeacherNameModal"></h5>
                                        <p class="mb-0 small">
                                            <i class="fas fa-envelope"></i> <span id="currentTeacherEmail"></span><br>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <button class="btn btn-danger btn-sm" id="removeTeacherBtn">
                                            <i class="fas fa-times"></i> Remove Teacher
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
                    <button type="button" class="btn btn-primary" id="confirmAssignBtn">
                        <i class="fas fa-check"></i> Assign Teacher
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    
    <script>
        const API_ROUTES = {
            getClasses: "{{ route('admin.classes.list') }}",
            getClassDetails: "{{ route('admin.classes.details', ['id' => ':id']) }}",
            getClassStudents: "{{ route('admin.classes.students', ['id' => ':id']) }}",
            getTeachers: "{{ route('admin.teachers.list') }}",
            assignTeacher: "{{ route('admin.classes.assign-teacher') }}",
            removeTeacher: "{{ route('admin.classes.remove-teacher') }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection