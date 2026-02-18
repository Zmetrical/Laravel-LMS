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
        .section-card {
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid #dee2e6;
        }
        
        .section-card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.15);
        }
        
        .section-card.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        .section-card.disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .section-card .card-body {
            padding: 1rem;
        }
        
        .capacity-progress {
            height: 8px;
            margin-top: 0.5rem;
        }
        
        .capacity-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
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
        <input type="hidden" id="current_semester_id" value="{{ $currentSemester->id ?? '' }}">
        
        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-lg-3">
                <!-- Filter Options -->
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Students</h3>
                    </div>
                    <div class="card-body">
                        <!-- Filter by Section -->
                        <div class="form-group">
                            <label>Current Section</label>
                            <select class="form-control" id="filter_section" style="width: 100%;">
                                <option value="">All Sections</option>
                            </select>
                        </div>

                        <!-- Search by Student -->
                        <div class="form-group mb-0">
                            <label>Search Student</label>
                            <input type="text" class="form-control" id="filter_student" 
                                   placeholder="Number or name...">
                        </div>
                    </div>
                </div>

                <!-- Target Section Card -->
                <div class="card card-primary card-outline mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bullseye mr-2"></i>Target Assignment</h3>
                    </div>
                    <div class="card-body">
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
                            <label>Target Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="target_level" name="level_id" required>
                                <option value="">Select Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">
                                        {{ $level->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label>Target Section <span class="text-danger">*</span></label>
                            <div id="targetSectionCards" class="mt-2">
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-arrow-up"></i>
                                    <p class="mb-0"><small>Select strand and level first</small></p>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" id="target_section" name="section_id">
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
                                    @if($currentSemester)
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                                <p>Loading students...</p>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                                <p>No active semester found. Please activate a semester first.</p>
                                            </td>
                                        </tr>
                                    @endif
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
    loadStudents: "{{ route('admin.section_assignment.load_students') }}",
    getFilterOptions: "{{ route('admin.section_assignment.get_filter_options') }}",
    getTargetSections: "{{ route('admin.section_assignment.get_target_sections') }}",
    getSectionCapacity: "{{ route('admin.section_assignment.get_section_capacity') }}",
    assignStudents: "{{ route('admin.section_assignment.assign_students') }}",
    redirectAfterSubmit: "{{ route('admin.list_student') }}"
};

const CURRENT_SEMESTER_ID = "{{ $currentSemester->id ?? '' }}";
const HAS_ACTIVE_SEMESTER = {{ $currentSemester ? 'true' : 'false' }};
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection