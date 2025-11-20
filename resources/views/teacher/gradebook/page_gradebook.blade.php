@extends('layouts.main-teacher')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .gradebook-input {
            width: 70px;
            text-align: center;
            padding: 4px;
        }
        .score-cell {
            position: relative;
        }
        .online-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 9px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .sticky-col {
            position: sticky;
            left: 0;
            background: white;
            z-index: 10;
        }
        .sticky-col-2 {
            position: sticky;
            left: 80px;
            background: white;
            z-index: 10;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('teacher.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('teacher.classes') }}">Classes</a></li>
        <li class="breadcrumb-item active">Gradebook</li>
    </ol>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Main Card -->
            <div class="card card-primary card-outline card-tabs">
                <div class="card-header p-0 pt-1 border-bottom-0">
                    <div class="d-flex justify-content-between align-items-center px-3 pb-2">
                        <div class="form-group mb-0">
                            <h5 class="mb-0">
                                <i class="fas fa-book"></i> <span id="className">Loading...</span>
                            </h5>
                        </div>
                        <div>
                            <button class="btn btn-info btn-sm" id="addColumnBtn">
                                <i class="fas fa-plus"></i> Add Column
                            </button>
                            <button class="btn btn-primary btn-sm" id="importExcelBtn">
                                <i class="fas fa-upload"></i> Import Excel
                            </button>
                            <input type="file" id="excelFileInput" accept=".xlsx,.xls" style="display: none;">
                            <button id="exportGradebookBtn" class="btn btn-primary btn-sm">
                                <i class="fas fa-download"></i> Export Excel
                            </button>
                        </div>
                    </div>
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
                        <!-- Written Work Tab -->
                        <div class="tab-pane fade show active" id="ww" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm" id="wwTable">
                                    <thead class="thead-light">
                                        <tr id="wwHeaderRow">
                                            <th class="sticky-col">USN</th>
                                            <th class="sticky-col-2">Student Name</th>
                                        </tr>
                                    </thead>
                                    <tbody id="wwTableBody">
                                        <tr>
                                            <td colspan="100" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Loading...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Performance Tasks Tab -->
                        <div class="tab-pane fade" id="pt" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm" id="ptTable">
                                    <thead class="thead-light">
                                        <tr id="ptHeaderRow">
                                            <th class="sticky-col">USN</th>
                                            <th class="sticky-col-2">Student Name</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ptTableBody">
                                        <tr>
                                            <td colspan="100" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Loading...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Quarterly Assessment Tab -->
                        <div class="tab-pane fade" id="qa" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm" id="qaTable">
                                    <thead class="thead-light">
                                        <tr id="qaHeaderRow">
                                            <th class="sticky-col">USN</th>
                                            <th class="sticky-col-2">Student Name</th>
                                        </tr>
                                    </thead>
                                    <tbody id="qaTableBody">
                                        <tr>
                                            <td colspan="100" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Loading...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Summary Tab -->
                        <div class="tab-pane fade" id="summary" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>USN</th>
                                            <th>Student Name</th>
                                            <th class="text-center">Written Work<br><small id="wwPercLabel">(0%)</small></th>
                                            <th class="text-center">Performance Task<br><small id="ptPercLabel">(0%)</small></th>
                                            <th class="text-center">Quarterly Assessment<br><small id="qaPercLabel">(0%)</small></th>
                                            <th class="text-center">Initial Grade</th>
                                            <th class="text-center bg-primary">Quarterly Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody id="summaryTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Loading...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Statistics -->
                            <div class="row mt-4">
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3 id="avgGrade">0.00</h3>
                                            <p>Class Average</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h3 id="highestGrade">0.00</h3>
                                            <p>Highest Grade</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-trophy"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h3 id="lowestGrade">0.00</h3>
                                            <p>Lowest Grade</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-primary">
                                        <div class="inner">
                                            <h3 id="passingRate">0%</h3>
                                            <p>Passing Rate (≥75)</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Add Column Modal -->
    <div class="modal fade" id="addColumnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add Gradebook Column</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="addColumnForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Component Type <span class="text-danger">*</span></label>
                            <select class="form-control" name="component_type" required>
                                <option value="">Select Type</option>
                                <option value="WW">Written Work</option>
                                <option value="PT">Performance Task</option>
                                <option value="QA">Quarterly Assessment</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Column Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="column_name" 
                                   placeholder="e.g., WW1, PT1" required>
                            <small class="text-muted">Must be unique within component type</small>
                        </div>
                        <div class="form-group">
                            <label>Maximum Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="max_points" 
                                   step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Source Type <span class="text-danger">*</span></label>
                            <select class="form-control" name="source_type" id="sourceType" required>
                                <option value="manual">Manual Entry</option>
                                <option value="online">From Online Quiz</option>
                            </select>
                        </div>
                        <div class="form-group" id="quizSelectGroup" style="display: none;">
                            <label>Select Quiz <span class="text-danger">*</span></label>
                            <select class="form-control" name="quiz_id" id="quizSelect">
                                <option value="">Loading quizzes...</option>
                            </select>
                            <small class="text-muted">Scores will be synced automatically</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Add Column
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    <script>
        const CLASS_ID = {{ $classId }};
        const API_ROUTES = {
            getGradebook: `/teacher/gradebook/${CLASS_ID}/data`,
            addColumn: `/teacher/gradebook/${CLASS_ID}/column/add`,
            updateScore: `/teacher/gradebook/score/update`,
            getQuizzes: `/teacher/gradebook/${CLASS_ID}/quizzes`,
            exportGradebook: `/teacher/gradebook/${CLASS_ID}/export`,
            importGradebook: `/teacher/gradebook/${CLASS_ID}/import`
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection