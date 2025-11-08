@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-user-clock"></i> Irregular Students
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">Irregular Students</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- School Year Info -->
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-calendar-alt"></i> School Year 2024-2025</h5>
                Manage custom class enrollments for irregular students.
            </div>

            <!-- Filters Card -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Student Filters</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search Student</label>
                                <input type="text" class="form-control" id="irregularSearchInput"
                                    placeholder="Name or Student Number...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Grade Level</label>
                                <select class="form-control" id="irregularGradeFilter">
                                    <option value="">All Grades</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Strand</label>
                                <select class="form-control" id="irregularStrandFilter">
                                    <option value="">All Strands</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Enrollment Status</label>
                                <select class="form-control" id="enrollmentStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="enrolled">Has Classes</option>
                                    <option value="not_enrolled">No Classes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button class="btn btn-info btn-block" id="resetFiltersBtn">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="text-center" style="display: none;">
                <i class="fas fa-spinner fa-spin fa-3x text-info"></i>
                <p class="mt-2">Loading students...</p>
            </div>

            <!-- Students Container -->
            <div id="irregularStudentsContainer" class="row"></div>
        </div>
    </section>

    <!-- Student Class Enrollment Modal -->
    <div class="modal fade" id="studentClassModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title">
                        <i class="fas fa-book"></i> <span id="modalStudentName">Student</span> - Class Enrollment
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Student Info Summary -->
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-body p-2">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Student Number:</strong> <span id="modalStudentNumber"></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Grade Level:</strong> <span id="modalStudentLevel"></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Strand:</strong> <span id="modalStudentStrand"></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Enrolled Classes:</strong> <span id="modalEnrolledCount" class="badge badge-info">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Classes Section -->
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list"></i> Available Classes</h3>
                            <div class="card-tools">
                                <input type="text" class="form-control form-control-sm" id="availableClassSearch" 
                                    placeholder="Search classes..." style="width: 250px;">
                            </div>
                        </div>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead style="position: sticky; top: 0; background-color: #fff; z-index: 10;">
                                    <tr>
                                        <th width="50">Select</th>
                                        <th>Class Code</th>
                                        <th>Class Name</th>
                                        <th>Teacher</th>
                                        <th width="100" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="availableClassesBody">
                                    <!-- Will be populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Enrolled Classes Section -->
                    <div class="card card-outline card-primary mt-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-check-circle"></i> Enrolled Classes</h3>
                        </div>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead style="position: sticky; top: 0; background-color: #fff; z-index: 10;">
                                    <tr>
                                        <th>Class Code</th>
                                        <th>Class Name</th>
                                        <th>Teacher</th>
                                        <th width="100" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="enrolledClassesBody">
                                    <!-- Will be populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const API_ROUTES = {
            getStudents: "{{ route('admin.irregular.students.data') }}",
            getStudentClasses: "{{ route('admin.irregular.student.classes', ['id' => ':id']) }}",
            enrollClass: "{{ route('admin.irregular.enroll.class') }}",
            unenrollClass: "{{ route('admin.irregular.unenroll.class') }}",
            getLevels: "{{ route('admin.levels.data') }}",
            getStrands: "{{ route('admin.strands.data') }}"
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection