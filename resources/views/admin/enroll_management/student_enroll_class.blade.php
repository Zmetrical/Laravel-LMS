@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-book"></i> Class Enrollment
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.enroll_student') }}">Irregular Students</a></li>
                <li class="breadcrumb-item active">Class Enrollment</li>
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
                Manage custom class enrollments for this student.
            </div>

            <div class="row">
                <!-- Student Info & Available Classes -->
                <div class="col-md-5">
                    <!-- Student Info Card -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user"></i> Student Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.enroll_student') }}" class="btn btn-tool">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body" id="studentInfoContainer">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Loading student info...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Available Classes Card -->
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list"></i> Available Classes</h3>
                        </div>
                        <div class="card-body">
                            <!-- Search -->
                            <div class="form-group">
                                <input type="text" class="form-control" id="availableClassSearch" 
                                       placeholder="Search classes...">
                            </div>

                            <!-- Available Classes List -->
                            <div id="availableClassesContainer" style="max-height: 450px; overflow-y: auto;">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
                                    <p class="mt-2">Loading classes...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Classes -->
                <div class="col-md-7">
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-check-circle"></i> Enrolled Classes
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-info" id="enrolledCountBadge">0 Classes</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="enrolledClassesLoadingIndicator" class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-info"></i>
                                <p class="mt-2">Loading enrolled classes...</p>
                            </div>

                            <div id="enrolledClassesTableContainer" style="display: none;">
                                <table class="table table-bordered table-hover" id="enrolledClassesTable">
                                    <thead class="bg-info">
                                        <tr>
                                            <th>Class Code</th>
                                            <th>Class Name</th>
                                            <th>Teacher</th>
                                            <th width="100">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="enrolledClassesBody">
                                    </tbody>
                                </table>
                            </div>

                            <div id="noEnrolledClassesMessage" class="text-center py-5" style="display: none;">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No classes enrolled yet</p>
                                <p class="text-muted small">Select classes from the left panel to enroll</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    
    <script>
        const STUDENT_ID = {{ $studentId }};
        const API_ROUTES = {
            getStudentInfo: "{{ route('admin.student.info', ['id' => $studentId]) }}",
            getStudentClasses: "{{ route('admin.student.classes', ['id' => $studentId]) }}",
            enrollClass: "{{ route('admin.enroll.class') }}",
            unenrollClass: "{{ route('admin.unenroll.class') }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection