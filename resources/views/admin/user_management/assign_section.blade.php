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

    <style>
        .section-capacity-card {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .semester-quick-btn {
            margin-bottom: 0.5rem;
        }

        .semester-quick-btn.active {
            font-weight: 600;
        }
    </style>
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
                        <!-- Source Semester Selection -->
                        <div class="form-group">
                            <label>Previous Semester <span class="text-danger">*</span></label>
                            
                            <!-- Quick Access Buttons -->
                            <div class="mb-2" id="sourceSemesterQuick"></div>
                            
                            <!-- Semester Dropdown -->
                            <select class="form-control" id="source_semester" required>
                                <option value="">Select semester...</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" 
                                        data-status="{{ $semester->status }}">
                                        {{ $semester->year_code }} - {{ $semester->semester_name }}
                                        @if($semester->status === 'active') (Active) @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Load students from this semester
                            </small>
                        </div>

                        <hr>

                        <!-- Filter Options -->
                        <div id="filterOptions" style="display: none;">
                            <label class="mb-2">Filter Students By</label>
                            
                            <!-- Filter by Section -->
                            <div class="form-group">
                                <label class="text-muted" style="font-size: 0.875rem;">
                                    <i class="fas fa-users mr-1"></i> Section
                                </label>
                                <select class="form-control" id="filter_section" style="width: 100%;">
                                    <option value="">All Sections</option>
                                </select>
                            </div>

                            <!-- Search by Student -->
                            <div class="form-group mb-0">
                                <label class="text-muted" style="font-size: 0.875rem;">
                                    <i class="fas fa-search mr-1"></i> Search Student
                                </label>
                                <input type="text" class="form-control" id="filter_student" 
                                       placeholder="Number or name...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Target Section Card -->
                <div class="card card-primary card-outline mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bullseye mr-2"></i>Target Assignment</h3>
                    </div>
                    <div class="card-body">
                        <!-- Target Semester Selection -->
                        <div class="form-group">
                            <label>Target Semester <span class="text-danger">*</span></label>
                            
                            <!-- Quick Access Buttons -->
                            <div class="mb-2" id="targetSemesterQuick"></div>
                            
                            <!-- Semester Dropdown -->
                            <select class="form-control" id="target_semester" name="semester_id" required>
                                <option value="">Select semester...</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" 
                                        data-status="{{ $semester->status }}"
                                        @if($semester->status === 'active') selected @endif>
                                        {{ $semester->year_code }} - {{ $semester->semester_name }}
                                        @if($semester->status === 'active') (Active) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Strand <span class="text-danger">*</span></label>
                            <select class="form-control" id="target_strand" name="strand_id" required>
                                <option value="">Select Strand</option>
                                @foreach($strands as $strand)
                                    <option value="{{ $strand->id }}">
                                        {{ $strand->name }} ({{ $strand->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Section <span class="text-danger">*</span></label>
                            <select class="form-control" id="target_section" name="section_id" required>
                                <option value="">Select section...</option>
                            </select>
                        </div>

                        <!-- Capacity Info Display -->
                        <div id="capacityInfo" style="display: none;"></div>
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
                                <button type="button" class="btn btn-secondary" id="removeSelectedBtn">
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
                                            <p>Select a source semester to load students</p>
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
    loadStudentsBySemester: "{{ route('admin.section_assignment.load_students_by_semester') }}",
    getSectionsBySemester: "{{ route('admin.section_assignment.get_sections_by_semester') }}",
    getTargetSections: "{{ route('admin.section_assignment.get_target_sections') }}",
    getSectionCapacity: "{{ route('admin.section_assignment.get_section_capacity') }}",
    assignStudents: "{{ route('admin.section_assignment.assign_students') }}",
    redirectAfterSubmit: "{{ route('admin.list_student') }}"
};

// Pass semesters data to JavaScript
const SEMESTERS_DATA = @json($semesters);
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection