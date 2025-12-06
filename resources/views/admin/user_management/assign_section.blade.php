@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Section Assignment</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Mode Selector -->
    <div class="card">
        <div class="card-body p-0">
            <div class="row no-gutters">
                <div class="col-md-6 mode-tab active" id="individualTab">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Individual Assignment</h5>
                    <small class="text-muted">Assign students one by one or in groups</small>
                </div>
                <div class="col-md-6 mode-tab" id="bulkTab">
                    <h5 class="mb-0"><i class="fas fa-users-cog mr-2"></i>Bulk Promotion</h5>
                    <small class="text-muted">Promote entire sections to next level</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Individual Assignment Mode -->
    <div id="individualMode">
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-md-3">
                <div class="card card-primary card-outline sticky-top">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Students</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Source Semester</label>
                            <select class="form-control" id="sourceSemester">
                                <option value="">All Semesters</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" 
                                        {{ $semester->id == $activeSemester->id ? 'selected' : '' }}>
                                        {{ $semester->school_year_code }} - {{ $semester->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Student Type</label>
                            <select class="form-control" id="studentType">
                                <option value="">All Types</option>
                                <option value="regular">Regular</option>
                                <option value="irregular">Irregular</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Strand</label>
                            <select class="form-control" id="sourceStrand">
                                <option value="">All Strands</option>
                                @foreach($strands as $strand)
                                    <option value="{{ $strand->id }}">{{ $strand->code }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Year Level</label>
                            <select class="form-control" id="sourceLevel">
                                <option value="">All Levels</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Current Section</label>
                            <select class="form-control" id="sourceSection">
                                <option value="">All Sections</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-primary btn-block" id="loadStudents">
                            <i class="fas fa-search mr-2"></i>Load Students
                        </button>

                        <button type="button" class="btn btn-secondary btn-block" id="clearFilters">
                            <i class="fas fa-undo mr-2"></i>Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Assignment Panel -->
                <div class="card card-secondary card-outline sticky-top mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-arrow-right mr-2"></i>Assign To</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span id="selectedCount">0</span> student(s) selected
                        </div>

                        <div class="form-group">
                            <label>Target Semester</label>
                            <select class="form-control" id="targetSemester" required>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" 
                                        {{ $semester->id == $activeSemester->id ? 'selected' : '' }}>
                                        {{ $semester->school_year_code }} - {{ $semester->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Strand</label>
                            <select class="form-control" id="targetStrand" required>
                                <option value="">Select Strand</option>
                                @foreach($strands as $strand)
                                    <option value="{{ $strand->id }}">{{ $strand->code }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Level</label>
                            <select class="form-control" id="targetLevel" required>
                                <option value="">Select Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Section</label>
                            <select class="form-control" id="targetSection" required>
                                <option value="">Select Section</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-primary btn-block" id="assignStudents">
                            <i class="fas fa-check mr-2"></i>Assign Selected
                        </button>
                    </div>
                </div>
            </div>

            <!-- Student List -->
            <div class="col-md-9">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Students</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-sm btn-secondary" id="selectAll">
                                <i class="fas fa-check-double mr-1"></i>Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="deselectAll">
                                <i class="fas fa-times mr-1"></i>Deselect All
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="studentsList">
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p>Use filters and click "Load Students" to begin</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Promotion Mode -->
    <div id="bulkMode" style="display: none;">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-upload mr-2"></i>Source Configuration</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Source Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="bulkSourceSemester" required>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}">
                                        {{ $semester->school_year_code }} - {{ $semester->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Source Strand <span class="text-danger">*</span></label>
                            <select class="form-control" id="bulkSourceStrand" required>
                                <option value="">Select Strand</option>
                                @foreach($strands as $strand)
                                    <option value="{{ $strand->id }}">{{ $strand->code }} - {{ $strand->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Source Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="bulkSourceLevel" required>
                                <option value="">Select Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="button" class="btn btn-primary btn-block" id="loadPromotionSummary">
                            <i class="fas fa-chart-bar mr-2"></i>Load Summary
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-download mr-2"></i>Target Configuration</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Target Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="bulkTargetSemester" required>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" 
                                        {{ $semester->id == $activeSemester->id ? 'selected' : '' }}>
                                        {{ $semester->school_year_code }} - {{ $semester->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="bulkTargetLevel" required>
                                <option value="">Select Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Only <strong>regular</strong> students will be promoted automatically. Irregular students should use Individual Assignment.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mapping Table -->
        <div class="card card-primary card-outline" id="mappingCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Section Mapping</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>Source Section</th>
                                <th>Regular Students</th>
                                <th>Target Section</th>
                            </tr>
                        </thead>
                        <tbody id="mappingTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-primary btn-lg float-right" id="executeBulkPromotion">
                    <i class="fas fa-arrow-up mr-2"></i>Execute Promotion
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
const API_ROUTES = {
    getStudentsByFilter: "{{ route('admin.sections.students.filter') }}",
    getSections: "{{ route('sections.data') }}",
    getAvailableSections: "{{ route('admin.sections.available') }}",
    assignStudents: "{{ route('admin.sections.assign.students') }}",
    getPromotionSummary: "{{ route('admin.sections.promotion.summary') }}",
    bulkPromote: "{{ route('admin.sections.promotion.bulk') }}"
};
</script>

<script src="{{ asset('js/user_management/assign_section.js') }}"></script>
@endsection