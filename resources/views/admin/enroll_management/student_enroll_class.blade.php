@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.student_irreg_class_enrollment') }}">Irregular Student List</a></li>
        <li class="breadcrumb-item active">Class Enrollment</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">

        <!-- School Year Info -->
        <div class="alert alert-dark alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="icon fas fa-calendar-alt"></i> {{ $activeSemesterDisplay ?? 'No Active Semester' }}</h5>
        </div>

        <div class="row">
            <!-- Student Info & Available Classes -->
            <div class="col-md-5">
                <!-- Student Info Card -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Student Information</h3>
                    </div>
                    <div class="card-body" id="studentInfoContainer">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-secondary"></i>
                            <p class="mt-2">Loading student info...</p>
                        </div>
                    </div>
                </div>

                <!-- Available Classes Card -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Available Classes</h3>
                        <div class="card-tools">
                            <span class="badge badge-primary" id="selectedCountBadge">0</span>
                            <span class="text-muted ml-1">selected</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search -->
                        <div class="form-group">
                            <input type="text" class="form-control" id="availableClassSearch" 
                                    placeholder="Search classes...">
                        </div>

                        <!-- Selection Controls -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-primary" id="selectAllClassesBtn">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>

                        <!-- Available Classes List -->
                        <div id="availableClassesContainer" style="max-height: 400px; overflow-y: auto;">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-secondary"></i>
                                <p class="mt-2">Loading classes...</p>
                            </div>
                        </div>

                        <!-- Enroll Button -->
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary btn-block" id="enrollSelectedBtn" disabled>
                                <i class="fas fa-plus-circle"></i> Enroll Selected Classes
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrolled Classes -->
            <div class="col-md-7">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-check-circle"></i> Enrolled Classes
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-primary" id="enrolledCountBadge">0 Classes</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="enrolledClassesLoadingIndicator" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-secondary"></i>
                            <p class="mt-2">Loading enrolled classes...</p>
                        </div>

                        <div id="enrolledClassesTableContainer" style="display: none;">
                            <table class="table table-bordered table-hover" id="enrolledClassesTable">
                                <thead class="thead-primary">
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Teacher</th>
                                        <th width="100" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="enrolledClassesBody">
                                </tbody>
                            </table>
                        </div>

                        <div id="noEnrolledClassesMessage" class="text-center py-5" style="display: none;">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No classes enrolled yet</p>
                            <p class="text-muted small">Select classes from the left panel and click "Enroll Selected Classes"</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
    
    <script>
        const STUDENT_ID = {{ $studentId }};
        const API_ROUTES = {
            getStudentInfo: "{{ route('admin.students.info', ['id' => $studentId]) }}",
            getStudentClasses: "{{ route('admin.students.classes', ['id' => $studentId]) }}",
            enrollClass: "{{ route('admin.students.enroll') }}",
            unenrollClass: "{{ route('admin.students.unenroll') }}"
        };

        const ACTIVE_SEMESTER_ID = {{ $activeSemester->semester_id ?? 'null' }};
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection