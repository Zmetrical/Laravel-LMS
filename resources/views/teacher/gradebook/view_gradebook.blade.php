@extends('layouts.main-teacher')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .table-gradebook {
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .table-gradebook thead th {
            background-color: #fff;
            color: black;
            font-weight: 600;
            vertical-align: top;
            padding: 10px 5px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table-gradebook tbody td {
            padding: 8px 5px;
            text-align: center;
        }
        
        .table-gradebook .student-info::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 1px;
            background-color: #fff;
        }
        
        .table-gradebook .score-cell {
            text-align: center;
        }
        
        .table-gradebook .total-cell {
            background-color: #fff;
            font-weight: 600;
        }
        
        .table-gradebook .grade-cell {
            background-color: #fff;
            color: black;
            font-weight: 700;
            font-size: 14px;
        }
        
        .component-header {
            background-color: #fff !important;
        }
        
        .online-badge {
            font-size: 10px;
            padding: 2px 5px;
            margin-left: 3px;
        }
        
        .column-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            line-height: 1.3;
        }
        
        .column-title {
            font-size: 13px;
            font-weight: 700;
        }
        
        .column-points {
            font-size: 11px;
            opacity: 0.9;
            font-weight: normal;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .summary-table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .summary-header th {
            background-color: #fff !important;
            color: black !important;
            font-weight: 600;
            vertical-align: top;
            padding: 10px 5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .summary-header th:first-child,
        .summary-header th:nth-child(2) {
            position: sticky;
            left: 0;
            z-index: 11;
            background-color: #fff;
        }

        .summary-header th:nth-child(2) {
            left: 120px;
        }

        .disabled-column {
            background-color: #f5f5f5 !important;
            opacity: 0.6;
        }

        .gender-separator {
            background-color: #2a347e !important;
            color: #fff !important;
            font-weight: 700;
            text-align: left !important;
            padding: 8px 10px !important;
        }

        .gender-separator td {
            text-align: left !important;
            position: sticky;
            left: 0;
            z-index: 8;
            background-color: #2a347e;
        }

        .loading-row td,
        .empty-state-row td {
            text-align: center !important;
            padding: 40px !important;
            background-color: #fff;
        }

        .btn-group-quarter .btn {
            border-radius: 0;
        }

        .btn-group-quarter .btn:first-child {
            border-top-left-radius: 0.25rem;
            border-bottom-left-radius: 0.25rem;
        }

        .btn-group-quarter .btn:last-child {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label {
            margin: 0;
            font-weight: 600;
            white-space: nowrap;
        }

        .remarks-passed {
            color: black;
            font-weight: 700;
        }

        .remarks-failed {
            color: black;
            font-weight: 700;
        }

        #finalGradeTable {
            display: none;
        }

        .row-missing-grade {
            background-color: rgba(255, 193, 7, 0.15) !important;
        }

        .row-missing-grade:hover {
            background-color: rgba(255, 193, 7, 0.25) !important;
        }

        .missing-score {
            background-color: rgba(255, 193, 7, 0.3) !important;
            font-weight: 600;
        }

        .final-grade-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            text-align: right;
        }

        #submitFinalGradeBtn {
            display: none;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('teacher.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('teacher.list_class') }}">Classes</a></li>
        <li class="breadcrumb-item active">View Gradebook</li>
    </ol>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="mb-1">
                        <i class="fas fa-book"></i> {{ $class->class_name }}
                    </h3>
                </div>
                <div class="col-md-6 text-right">
                    <button class="btn btn-secondary btn-sm" id="editBtn">
                        <i class="fas fa-edit"></i> Edit Gradebook
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <div class="row align-items-center">

            <div class="col-md-6 filter-controls">

                <div class="filter-group">
                    <label><i class="fas fa-users"></i> Section:</label>
                    <select class="form-control form-control-sm" id="sectionFilter" style="width: 200px;" required>
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>View:</label>
                    <div class="btn-group btn-group-sm btn-group-quarter" role="group">
                        @foreach($quarters as $quarter)
                            <button type="button" 
                                    class="btn btn-outline-secondary quarter-btn" 
                                    data-quarter="{{ $quarter->id }}"
                                    data-type="quarter">
                                {{ $quarter->name }}
                            </button>
                        @endforeach
                        <button type="button" 
                                class="btn btn-outline-secondary quarter-btn" 
                                data-type="final">
                            Final Grade
                        </button>
                    </div>
                </div>

            </div>

            <div class="col-md-6 text-right">
                    <button class="btn btn-primary btn-sm" id="exportBtn">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                </div>

            </div>

        </div>
    </div>

    <div class="card card-outline card-tabs">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="custom-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="ww-tab" data-toggle="pill" href="#ww" role="tab">
                        <i class="fas fa-pen"></i> Written Work
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pt-tab" data-toggle="pill" href="#pt" role="tab">
                        <i class="fas fa-tasks"></i> Performance Tasks
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="qa-tab" data-toggle="pill" href="#qa" role="tab">
                        <i class="fas fa-clipboard-check"></i> Quarterly Assessment
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="summary-tab" data-toggle="pill" href="#summary" role="tab">
                        <i class="fas fa-chart-bar"></i> Summary
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="custom-tabs-content">
                <div class="tab-pane fade show active" id="ww" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm table-gradebook">
                            <thead>
                                <tr id="wwHeaderRow"></tr>
                            </thead>
                            <tbody id="wwBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="pt" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm table-gradebook">
                            <thead>
                                <tr id="ptHeaderRow"></tr>
                            </thead>
                            <tbody id="ptBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="qa" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm table-gradebook">
                            <thead>
                                <tr id="qaHeaderRow"></tr>
                            </thead>
                            <tbody id="qaBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="summary" role="tabpanel">
                    <div class="summary-table-wrapper">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="summary-header">
                                <tr>
                                    <th class="pb-4">USN</th>
                                    <th class="pb-4">Student Name</th>
                                    <th class="text-center">Written Work<br><small>({{ $class->ww_perc }}%)</small></th>
                                    <th class="text-center">Performance Task<br><small>({{ $class->pt_perc }}%)</small></th>
                                    <th class="text-center">Quarterly Assessment<br><small>({{ $class->qa_perce }}%)</small></th>
                                    <th class="text-center pb-4">Initial Grade</th>
                                    <th class="text-center pb-4">Quarterly Grade</th>
                                </tr>
                            </thead>
                            <tbody id="summaryTableBody"></tbody>
                        </table>
                    </div>

                    <div id="finalGradeTable">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="summary-header">
                                    <tr>
                                        <th class="pb-4">USN</th>
                                        <th class="pb-4">Student Name</th>
                                        <th class="text-center pb-4">Q1 Grade</th>
                                        <th class="text-center pb-4">Q2 Grade</th>
                                        <th class="text-center pb-4">Semester Average</th>
                                        <th class="text-center pb-4">Final Grade</th>
                                        <th class="text-center pb-4">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="finalGradeTableBody"></tbody>
                            </table>
                        </div>
                        
                        <div class="final-grade-actions">
                            <button type="button" class="btn btn-primary" id="submitFinalGradeBtn">
                                <i class="fas fa-save"></i> Submit Final Grades
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-file-excel"></i> Export Gradebook
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="exportInitialContent">
                    <div class="text-center mb-4">
                        <i class="fas fa-file-excel fa-3x text-primary"></i>
                    </div>
                    <p class="text-center mb-3">
                        <strong>Class:</strong> {{ $class->class_name }}
                    </p>
                    <p class="text-center mb-3">
                        <strong>Section:</strong> <span id="exportSectionName"></span>
                    </p>
                </div>

                <div id="exportProgressContent" style="display: none;">
                    <div class="text-center mb-3">
                        <i class="fas fa-spinner fa-spin fa-3x text-secondary"></i>
                    </div>
                    <h6 class="text-center mb-3">Generating Excel File...</h6>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                             role="progressbar" 
                             id="exportProgressBar"
                             style="width: 0%">
                            <span id="exportProgressText">0%</span>
                        </div>
                    </div>
                    <p class="text-center text-muted mt-3 mb-0">
                        <small>Please wait while we prepare your file...</small>
                    </p>
                </div>

                <div id="exportCompleteContent" style="display: none;">
                    <div class="text-center mb-3">
                        <i class="fas fa-check-circle fa-3x text-primary"></i>
                    </div>
                    <h6 class="text-center mb-3">Export Successful!</h6>
                    <p class="text-center text-muted mb-0">
                        Your download should begin shortly.
                    </p>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" id="exportDownloadBtn">
                    <i class="fas fa-download"></i> Download Excel
                </button>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="passcodeModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary">
                    <h5 class="modal-title text-white" id="passcodeModalTitle">
                        <i class="fas fa-lock"></i> Verify Passcode
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="passcodeForm">
                    <div class="modal-body">
                        <p class="text-muted mb-3" id="passcodeModalMessage">
                            <i class="fas fa-info-circle"></i> Please enter your passcode
                        </p>
                        <div class="form-group">
                            <label for="passcode">Passcode <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control" 
                                   id="passcode" 
                                   name="passcode" 
                                   placeholder="Enter your passcode"
                                   autocomplete="off"
                                   required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="verifyPasscodeBtn">
                            <i class="fas fa-check"></i> Verify
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const CLASS_ID = {{ $classId }};
        const QUARTERS = @json($quarters);
        const CLASS_INFO = @json($class);
        const ACTIVE_SEMESTER_ID = {{ DB::table('semesters')->where('status', 'active')->value('id') ?? 'null' }};
        
        const API_ROUTES = {
            editGradebook: "{{ route('teacher.gradebook.edit', ['classId' => $classId]) }}",
            verifyPasscode: "{{ route('teacher.gradebook.verify-passcode', ['classId' => $classId]) }}",
            getGradebook: "{{ route('teacher.gradebook.data', ['classId' => $classId]) }}",
            exportGradebook: "{{ route('teacher.gradebook.export', ['classId' => $classId]) }}",
            getFinalGrade: "{{ route('teacher.gradebook.final-grade', ['classId' => $classId]) }}",
            submitFinalGrades: "{{ route('teacher.gradebook.submit-final-grades', ['classId' => $classId]) }}",
            checkFinalGradesStatus: "{{ route('teacher.gradebook.check-final-status', ['classId' => $classId]) }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection