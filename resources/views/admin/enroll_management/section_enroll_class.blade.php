@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">

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
            <!-- School Year Info -->
            <div class="alert alert-dark alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-calendar-alt"></i> {{ $activeSemesterDisplay ?? 'No Active Semester' }}</h5>
            </div>

            <div class="row">
                <!-- Sections List -->
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list"></i> Select Section</h3>
                        </div>
                        <div class="card-body">
                            <!-- Search -->
                            <div class="form-group">
                                <input type="text" class="form-control" id="sectionSearch" placeholder="Search sections...">
                            </div>
                            
                            <!-- Filters -->
                            <div class="form-group">
                                <select class="form-control" id="levelFilter">
                                    <option value="">All Levels</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select class="form-control" id="strandFilter">
                                    <option value="">All Strands</option>
                                </select>
                            </div>

                            <!-- Sections List -->
                            <div id="sectionsListContainer" style="max-height: 500px; overflow-y: auto;">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2">Loading sections...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Classes -->
                <div class="col-md-8">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-book"></i> Enrolled Classes
                            </h3>
                            <div class="card-tools">
                                <button class="btn btn-primary btn-sm" id="enrollClassBtn" disabled>
                                    <i class="fas fa-plus"></i> Enroll Class
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="sectionInfoContainer" style="display: none;">
                                <div class="alert alert-light">
                                    <h5 id="selectedSectionName"></h5>
                                    <p class="mb-0" id="selectedSectionDetails"></p>
                                </div>
                            </div>

                            <div id="noSectionSelected" class="text-center py-5">
                                <i class="fas fa-arrow-left fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Please select a section to view enrolled classes</p>
                            </div>

                            <div id="enrolledClassesContainer" style="display: none;">
                                <div id="classesLoadingIndicator" class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2">Loading classes...</p>
                                </div>
                                <div id="classesTableContainer" style="display: none;">
                                    <table class="table table-bordered table-hover" id="enrolledClassesTable">
                                        <thead class="bg-primary">
                                            <tr>
                                                <th>Class Code</th>
                                                <th>Class Name</th>
                                                <th>Teachers</th>
                                                <th width="100">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="enrolledClassesBody">
                                        </tbody>
                                    </table>
                                </div>
                                <div id="noClassesMessage" class="text-center py-4" style="display: none;">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No classes enrolled yet</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Enroll Class Modal -->
    <div class="modal fade" id="enrollClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i> Enroll Class
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Class</label>
                        <select class="form-control" id="availableClassesSelect">
                            <option value="">Loading...</option>
                        </select>
                    </div>
                    <div id="classInfoContainer" style="display: none;">
                        <div class="alert alert-light">
                            <strong>Class Code:</strong> <span id="modalClassCode"></span><br>
                            <strong>Teachers:</strong> <span id="modalClassTeachers"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmEnrollBtn" disabled>
                        <i class="fas fa-check"></i> Enroll
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const API_ROUTES = {
            getSections: "{{ route('admin.sections.list') }}",
            getSectionClasses: "{{ route('admin.sections.classes.details', ['id' => ':id']) }}",
            getAvailableClasses: "{{ route('admin.classes.available', ['sectionId' => ':id']) }}",
            enrollClass: "{{ route('admin.sections.enroll', ['id' => ':id']) }}",
            removeClass: "{{ route('admin.sections.remove-class', ['sectionId' => ':sectionId', 'classId' => ':classId']) }}"
        };

        const ACTIVE_SEMESTER_ID = {{ $activeSemester->semester_id ?? 'null' }};

    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif

    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>

@endsection