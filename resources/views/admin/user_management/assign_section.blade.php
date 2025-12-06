@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
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
                <!-- Source Section Card -->
                <div class="card card-secondary card-outline sticky-top" style="top: 70px;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-graduation-cap mr-2"></i>Source Section</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-secondary">
                            <small><i class="fas fa-info-circle"></i> Select the previous section to load students</small>
                        </div>

                        <div class="form-group">
                            <label>Previous Semester</label>
                            <select class="form-control" id="source_semester">
                                <option value="">Any Semester</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}">
                                        {{ $semester->year_code }} - {{ $semester->semester_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Optional: Filter by semester</small>
                        </div>

                        <div class="form-group">
                            <label>Source Strand</label>
                            <select class="form-control" id="source_strand">
                                <option value="" selected disabled>Select Strand</option>
                                @foreach($strands as $strand)
                                    <option value="{{ $strand->id }}">{{ $strand->code }} - {{ $strand->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Source Year Level</label>
                            <select class="form-control" id="source_level">
                                <option value="" selected disabled>Select Year Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Source Section <span class="text-danger">*</span></label>
                            <select class="form-control" id="source_section">
                                <option value="" selected disabled>Select Section</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-secondary btn-block" id="loadStudentsBtn">
                            <i class="fas fa-download mr-2"></i> Load Students
                        </button>
                    </div>
                </div>

                <!-- Target Section Card -->
                <div class="card card-primary card-outline mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bullseye mr-2"></i>Target Section</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-primary">
                            <small><i class="fas fa-info-circle"></i> Where students will be assigned</small>
                        </div>

                        <div class="form-group">
                            <label>Target Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="target_semester" name="semester_id" required>
                                <option value="" selected disabled>Select Semester</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" 
                                        @if($semester->status === 'active') selected @endif>
                                        {{ $semester->year_code }} - {{ $semester->semester_name }}
                                        @if($semester->status === 'active') ⭐ @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Strand <span class="text-danger">*</span></label>
                            <select class="form-control" id="target_strand" required>
                                <option value="" selected disabled>Select Strand</option>
                                @foreach($strands as $strand)
                                    <option value="{{ $strand->id }}">{{ $strand->code }} - {{ $strand->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Year Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="target_level" required>
                                <option value="" selected disabled>Select Year Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Section <span class="text-danger">*</span></label>
                            <select class="form-control" id="target_section" name="section_id" required>
                                <option value="" selected disabled>Select Section</option>
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
                                            <p>Select a source section and click <strong>"Load Students"</strong> to begin</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Info Alert -->
                        <div class="alert alert-secondary mt-3">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Instructions:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Select source section from the left to load existing students</li>
                                <li>Choose target section where students will be assigned</li>
                                <li>Select students you want to assign (or use Select All)</li>
                                <li>Students will be enrolled in the target semester and all section classes</li>
                            </ul>
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

<script>
const API_ROUTES = {
    getSourceSections: "{{ route('admin.section_assignment.get_sections') }}",
    getTargetSections: "{{ route('admin.section_assignment.get_sections') }}",
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