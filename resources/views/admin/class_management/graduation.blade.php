@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .filter-card { background: #f8f9fa; border: 1px solid #e9ecef; }
        .filter-card .form-control {
            font-size: 0.875rem;
            height: calc(2.25rem + 2px);
        }
        .filter-card label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.schoolyears.index') }}">School Years</a></li>
        <li class="breadcrumb-item active">Graduation — SY {{ $school_year->code }}</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">

    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-primary card-outline">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h4 class="mb-0">
                                <i class="fas fa-graduation-cap mr-2"></i>
                                Graduation Management
                            </h4>
                            <small class="text-muted">School Year {{ $school_year->code }}</small>
                        </div>
                        <div class="d-flex align-items-center mt-2 mt-md-0">
                            @if($is_finalized)
                                <span class="badge badge-secondary badge-lg" style="font-size:.85rem;padding:.5rem .85rem;">
                                    <i class="fas fa-lock mr-1"></i> Finalized
                                </span>
                            @else
                                <button class="btn btn-primary btn-sm" id="finalizeBtn">
                                    <i class="fas fa-lock mr-1"></i> Finalize Graduation List
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- Left Sidebar --}}
        <div class="col-lg-3">

            {{-- Filters --}}
            <div class="card filter-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Students</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Search Student</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Number or Name...">
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <select class="form-control" id="filterSection">
                            <option value="">All Sections</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" id="filterType">
                            <option value="">All Types</option>
                            <option value="regular">Regular</option>
                            <option value="irregular">Irregular</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Eligibility</label>
                        <select class="form-control" id="filterEligibility">
                            <option value="all">All</option>
                            <option value="eligible">Eligible</option>
                            <option value="issues">With Issues</option>
                            <option value="missing">Missing Grades</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer p-2">
                    <button class="btn btn-secondary btn-block btn-sm" id="clearFilters">
                        <i class="fas fa-undo mr-1"></i> Clear Filters
                    </button>
                </div>
            </div>

            {{-- Bulk Graduation Action --}}
            @if(!$is_finalized)
            <div class="card card-primary card-outline mt-3" id="bulkActionCard">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-check-double mr-2"></i>Bulk Action</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Graduation Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="bulkGradStatus">
                            <option value="graduated">Graduated</option>
                            <option value="not_graduated">Not Graduated</option>
                        </select>
                    </div>

                </div>
                <div class="card-footer p-2">
                    <button class="btn btn-primary btn-block" id="applyBulkBtn" disabled>
                        <i class="fas fa-save mr-1"></i>
                        Apply to Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            @endif

        </div>

        {{-- Main Table --}}
        <div class="col-lg-9">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-1"></i> Grade 12 Students
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-primary" id="studentsCount">—</span>
                    </div>
                </div>
                <div class="card-body p-0">

                    {{-- Action row --}}
                    <div class="px-3 pt-3 pb-2 d-flex align-items-center" id="tableActions" style="display:none!important;">
                        @if(!$is_finalized)
                        <button type="button" class="btn btn-secondary btn-sm mr-2" id="selectAllBtn">
                            <i class="fas fa-check-square mr-1"></i> Select All
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="deselectAllBtn">
                            <i class="fas fa-square mr-1"></i> Deselect All
                        </button>
                        @endif
                    </div>

                    {{-- Loading --}}
                    <div id="tableLoading" class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2 text-muted">Loading students...</p>
                    </div>

                    {{-- Empty --}}
                    <div id="tableEmpty" class="text-center py-5" style="display:none;">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No students found.</p>
                    </div>

                    {{-- Table --}}
                    <div id="tableContainer" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0" id="graduationTable">
                                <thead class="bg-light">
                                    <tr>
                                        @if(!$is_finalized)
                                        <th width="40" class="text-center">
                                            <input type="checkbox" id="selectAllCheckbox">
                                        </th>
                                        @endif
                                        <th width="40">#</th>
                                        <th>Student</th>
                                        <th width="90" class="text-center">Type</th>
                                        <th width="100" class="text-center">Section</th>
                                        <th width="70" class="text-center">Subjects</th>
                                        <th width="70" class="text-center">Passed</th>
                                        <th width="70" class="text-center">Failed</th>
                                        <th width="70" class="text-center">INC</th>
                                        <th width="110" class="text-center">Eligibility</th>
                                        <th width="120" class="text-center">Status</th>
                                        <th width="60" class="text-center">Grades</th>
                                    </tr>
                                </thead>
                                <tbody id="graduationTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

{{-- Subject Grades Modal --}}
<div class="modal fade" id="studentDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-book mr-2"></i>
                    <span id="modalStudentName">—</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Class Code</th>
                                <th>Semester</th>
                                <th class="text-center">Final Grade</th>
                                <th class="text-center">Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="modalSubjectsBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="#" class="btn btn-primary" id="viewEvaluationBtn" target="_blank">
                    <i class="fas fa-external-link-alt mr-1"></i> View Evaluation Card
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const GRAD_ROUTES = {
            getStudents:       "{{ route('admin.graduation.students', ['schoolYearId' => $school_year->id]) }}",
            saveStudentRecord: "{{ route('admin.graduation.save-record', ['schoolYearId' => $school_year->id]) }}",
            finalize:          "{{ route('admin.graduation.finalize', ['schoolYearId' => $school_year->id]) }}",
            evaluationBase:    "{{ route('admin.grades.evaluation', ['student_number' => '__SN__']) }}",
            csrfToken:         "{{ csrf_token() }}",
        };
        const IS_FINALIZED = {{ $is_finalized ? 'true' : 'false' }};
    </script>
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endsection