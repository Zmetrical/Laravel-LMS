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
        <div class="row">

            <!-- Left: Available Classes -->
            <div class="col-md-4">

                <!-- Available Classes -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Available Classes</h3>
                        <div class="card-tools">
                            <span class="badge badge-secondary" id="selectedCountBadge">0</span>
                            <span class="text-muted ml-1">selected</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-3">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" class="form-control" id="availableClassSearch"
                                       placeholder="Search classes...">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-default" id="clearSelectionBtn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="availableClassesContainer" style="max-height: 380px; overflow-y: auto;">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0">Loading classes...</p>
                            </div>
                        </div>

                        <div class="p-3 border-top">
                            <button type="button" class="btn btn-primary btn-block" id="enrollSelectedBtn" disabled>
                                <i class="fas fa-plus-circle"></i> Enroll Selected Classes
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right: Student Info + Enrolled Classes -->
            <div class="col-md-8">

                <!-- Student Info -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Student Information</h3>
                    </div>
                    <div class="card-body" id="studentInfoContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2 mb-0">Loading student info...</p>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Classes -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-check-circle"></i> Enrolled Classes
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-secondary" id="enrolledCountBadge">0 Classes</span>
                        </div>
                    </div>
                    <div class="card-body p-0">

                        <div id="enrolledClassesLoadingIndicator" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2 mb-0">Loading enrolled classes...</p>
                        </div>

                        <div id="enrolledClassesTableContainer" style="display: none;">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Teacher</th>
                                        <th width="120" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="enrolledClassesBody">
                                </tbody>
                            </table>
                        </div>

                        <div id="noEnrolledClassesMessage" class="text-center py-5 text-muted" style="display: none;">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p class="mb-1">No classes enrolled yet</p>
                            <p class="small">Select classes from the left panel and click "Enroll Selected Classes"</p>
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