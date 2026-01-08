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
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Assign Section</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <form id="assign_section_form" method="POST">
        @csrf
        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-lg-3">
<!-- Source Selection Card -->
<div class="card card-secondary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-graduation-cap mr-2"></i>Source Selection</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label>Previous Semester </label>
            <select class="form-control" id="source_semester">
                <option value="">All Students in Section</option>
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}">
                        {{ $semester->year_code }} - {{ $semester->semester_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Source Type Toggle -->
        <div class="form-group">
            <label>Source Type</label>
            <div class="btn-group btn-block">
                <button type="button" class="btn btn-secondary active" id="sourceSectionBtn">
                    <i class="fas fa-users"></i> Section
                </button>
                <button type="button" class="btn btn-default" id="sourceStudentBtn">
                    <i class="fas fa-user"></i> Student
                </button>
            </div>
        </div>

        <!-- Source Section -->
        <div id="sourceSectionGroup">
            <div class="form-group mb-0">
                <label>Source Section <span class="text-danger">*</span></label>
                <select class="form-control select2" id="source_section" style="width: 100%;">
                    <option value="">Search for section...</option>
                </select>
            </div>
        </div>

        <!-- Source Student -->
        <div id="sourceStudentGroup" style="display: none;">
            <div class="form-group">
                <label>Search Student <span class="text-danger">*</span></label>
                <select class="form-control select2" id="source_student" style="width: 100%;">
                    <option value="">Type student number or name...</option>
                </select>
            </div>

            <button type="button" class="btn btn-secondary btn-block" id="addStudentBtn">
                <i class="fas fa-user-plus mr-2"></i> Add Student
            </button>
        </div>
    </div>
</div>

                <!-- Target Section Card -->
                <div class="card card-primary card-outline mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bullseye mr-2"></i>Target Section</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Target Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="target_semester" name="semester_id" required>
                                <option value="" selected disabled>Select Semester</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" 
                                        @if($semester->status === 'active') selected @endif>
                                        {{ $semester->year_code }} - {{ $semester->semester_name }}

                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Section <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="target_section" name="section_id" style="width: 100%;" required>
                                <option value="">Search for section...</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content - Student Grid -->
            <div class="col-lg-9">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users-cog mr-2"></i>Student Assignment</h3>
                        <div class="card-tools">
                            <span class="badge badge-primary" id="studentCount">0 Students</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Action Buttons -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-secondary mr-1" id="selectAllBtn">
                                    <i class="fas fa-check-square mr-1"></i> Select All
                                </button>
                                <button type="button" class="btn btn-secondary" id="deselectAllBtn">
                                    <i class="fas fa-square mr-1"></i> Deselect All
                                </button>
                            </div>
                            <div class="col-md-6 text-right">
                                <!-- Search Student Table -->
                                <div class="input-group" style="width: 250px; display: inline-flex;">
                                    <input type="text" class="form-control" id="tableSearchInput" placeholder="Search table...">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" id="clearTableSearchBtn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary ml-2" id="removeSelectedBtn">
                                    <i class="fas fa-trash mr-1"></i> Remove Selected
                                </button>
                            </div>
                        </div>

                        <!-- Student Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40" class="text-center">
                                            <input type="checkbox" id="selectAllCheckbox">
                                        </th>
                                        <th width="40" class="text-center">#</th>
                                        <th width="150">Student Number</th>
                                        <th width="250">Student Name</th>
                                        <th width="150">Current Section</th>
                                        <th width="120" class="text-center">Type</th>
                                    </tr>
                                </thead>
                                <tbody id="assignmentTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fas fa-arrow-left fa-2x mb-3"></i>
                                            <p>Select a source section or search for a student to begin</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-lg float-right" id="submitBtn" disabled>
                            <i class="fas fa-user-check mr-2"></i> Assign Selected Students
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>

<script>
const API_ROUTES = {
    searchSections: "{{ route('admin.section_assignment.search_sections') }}",
    searchStudents: "{{ route('admin.section_assignment.search_students') }}",
    loadStudents: "{{ route('admin.section_assignment.load_students') }}",
    assignStudents: "{{ route('admin.section_assignment.assign_students') }}",
    redirectAfterSubmit: "{{ route('admin.list_student') }}"
};
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection